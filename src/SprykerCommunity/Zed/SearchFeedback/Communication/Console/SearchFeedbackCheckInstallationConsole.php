<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Console;

use FilesystemIterator;
use PDO;
use PDOStatement;
use Propel\Runtime\Propel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SimpleXMLElement;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerCommunity\Client\SearchFeedback\Plugin\Catalog\SearchFeedbackSnapshotResultFormatterPlugin;
use SprykerCommunity\Client\SearchFeedback\Search\ReplayCapableSearch;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClient;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Diagnoses a search-feedback installation.
 *
 * This package's own README installation section has 9 steps, and — same as the sibling
 * spryker-community/search-debug and spryker-community/search-ranking packages' equivalent commands —
 * almost every one of them fails SILENTLY when missed: a forgotten DependencyProvider wire-up produces no
 * error, the ticket form simply never appears (or worse, appears but 404s on submit). This checks every
 * prerequisite reachable from the CLI and names the exact remedy for whatever is wrong.
 *
 * Deliberately honest about its own limits, same posture as
 * {@see \SprykerCommunity\Zed\SearchDebug\Communication\Console\SearchDebugCheckInstallationConsole}: it
 * runs in Zed, so it cannot introspect the Yves DI container (steps 4-5) or confirm the ticket-form
 * template renders correctly (step 5's nested-`<form>` gotcha). It cannot judge whether an adopter's ACL
 * setup is the one they intended either — but it does report whether ANY restricted back-office role can
 * reach this package's pages at all, which is the part a DB row CAN attest to. It also flags the single
 * most notorious silent-failure point this package's own README calls out by name: the Backend Gateway
 * router needs its OWN cache warmed
 * (step 8) — every other Zed page works fine while ticket submission alone 404s until that runs.
 *
 * Complementary counterpart:
 * {@see \SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController} (the
 * `/search-feedback-widget/check-installation` page) closes exactly the Yves-side gap this command names
 * above — the Twig functions, the submit-ticket route, and the frozen-replay event listener (step 11) — by
 * running from inside the real Yves DI container. It does not re-check anything this command already
 * covers; run both for a full picture.
 *
 * @method \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacadeInterface getFacade()
 * @method \SprykerCommunity\Zed\SearchFeedback\Communication\SearchFeedbackCommunicationFactory getFactory()
 */
class SearchFeedbackCheckInstallationConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-feedback:check-installation';

    /**
     * @var string
     */
    public const COMMAND_DESCRIPTION = 'Diagnoses a search-feedback installation: core namespace, plugin classes, ticket-table reachability, navigation entries, and back-office ACL reachability.';

    /**
     * @var string
     */
    protected const CORE_NAMESPACE = 'SprykerCommunity';

    /**
     * This package's own navigation.xml, relative to this console's directory — the source of truth for
     * which page keys a project is expected to have copied.
     *
     * @var string
     */
    protected const OWN_NAVIGATION_XML_RELATIVE_PATH = '/../../../SearchFeedbackGui/Communication/navigation.xml';

    /**
     * This package's root, relative to this console's directory.
     *
     * @var string
     */
    protected const PACKAGE_ROOT_RELATIVE_PATH = '/../../../../../..';

    /**
     * The project-level Client Factory override that README step 11 asks for — the seam that actually
     * swaps a live ES call for the frozen snapshot. Relative to `APPLICATION_ROOT_DIR`.
     *
     * @var string
     */
    protected const SEARCH_ELASTICSEARCH_FACTORY_OVERRIDE_RELATIVE_PATH = '/src/Pyz/Client/SearchElasticsearch/SearchElasticsearchFactory.php';

    /**
     * The JSON-blob columns on the snapshot table — must be LONGTEXT (Propel's `CLOB` type), not the plain
     * TEXT a `LONGVARCHAR` column produces. See {@see checkSnapshotColumnTypes()}.
     *
     * @var array<string>
     */
    protected const SNAPSHOT_LONGTEXT_COLUMN_NAMES = ['raw_response', 'query_dsl', 'request_parameters', 'term_vector_snapshot'];

    /**
     * @var string
     */
    protected const SNAPSHOT_TABLE_NAME = 'spy_search_feedback_ticket_srp_snapshot';

    /**
     * Every permission plugin this package ships — a plugin being registered in code does not mean Spryker
     * knows about it; that only happens once it's synced into `spy_permission`
     * ({@see checkPermissionsSynced()}).
     *
     * @var array<string>
     */
    protected const PERMISSION_KEYS = [
        SubmitSearchFeedbackTicketPermissionPlugin::KEY,
        ViewSearchFeedbackTicketReplayPermissionPlugin::KEY,
    ];

    /**
     * The locale whose catalog defines the expected key set; the others are kept at parity with it.
     *
     * @var string
     */
    protected const TRANSLATION_REFERENCE_LOCALE = 'en_US';

    /**
     * @var string
     */
    protected const PATTERN_TWIG_TRANS = '/(?<![\\w\\\\])([\'"])((?:\\\\.|(?!\\1).)*)\\1\\s*\\|\\s*trans/';

    /**
     * @var string
     */
    protected const PATTERN_PHP_TRANS = '/->(?:trans|translate)\\(\\s*([\'"])((?:\\\\.|(?!\\1).)*)\\1/';

    /**
     * @var array<string>
     */
    protected array $failures = [];

    /**
     * @var array<string>
     */
    protected array $warnings = [];

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::COMMAND_DESCRIPTION);

        parent::configure();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $input is mandated by the Console base class.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkCoreNamespace($output);
        $this->checkPluginClasses($output);
        $this->checkTicketTable($output);
        $this->checkNavigationRegistered($output);
        $this->checkBackOfficeAccess($output);
        $this->checkZedTranslationCatalogComplete($output);
        $this->checkFrozenReplayWiring($output);
        $this->checkSnapshotColumnTypes($output);
        $this->checkPermissionsSynced($output);

        $output->writeln('');

        foreach ($this->warnings as $warning) {
            $output->writeln(sprintf('<comment>! %s</comment>', $warning));
        }

        if ($this->failures !== []) {
            foreach ($this->failures as $failure) {
                $output->writeln(sprintf('<error>✗ %s</error>', $failure));
            }

            return static::CODE_ERROR;
        }

        $output->writeln('<info>Everything checkable from the CLI is in place.</info>');
        $output->writeln('Not verifiable from Zed — check these separately:');
        $output->writeln('  - Yves plugin registration (route + Twig function providers, step 4) and the ticket-form');
        $output->writeln('    template include (step 5) — run the Yves counterpart below for the first of these.');
        $output->writeln('  - whether your ACL setup is the one you intended (step 11) — that SOME role can reach these');
        $output->writeln('    pages IS checked above; which roles SHOULD is a decision no command can make for you.');
        $output->writeln('  - the Backend Gateway router cache (step 8) — its OWN cache, separate from every other Zed');
        $output->writeln('    page\'s router; run `vendor/bin/console router:cache:warm-up:backend-gateway` if ticket');
        $output->writeln('    submission alone 404s while everything else works.');
        $output->writeln('  - three of the four frozen-replay registrations (step 11): the Client');
        $output->writeln('    CATALOG_SEARCH_RESULT_FORMATTER_PLUGINS entry, and both Permission DependencyProvider');
        $output->writeln('    entries for ViewSearchFeedbackTicketReplayPermissionPlugin — this command can only see the');
        $output->writeln('    SearchElasticsearchFactory override and whether the permission is SYNCED (both checked');
        $output->writeln('    above); whether the plugin is actually REGISTERED in the DependencyProvider is DI wiring');
        $output->writeln('    no console command can introspect. The Yves EventDispatcher entry for that same step, and');
        $output->writeln('    the search-results template\'s searchFeedbackSnapshot mapping, are checked by the Yves');
        $output->writeln('    counterpart below.');
        $output->writeln('');
        $output->writeln('The first of those is checkable from Yves: load /search-feedback-widget/check-installation as a');
        $output->writeln('permitted customer (SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController).');

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkCoreNamespace(OutputInterface $output): void
    {
        $coreNamespaces = Config::get(KernelConstants::CORE_NAMESPACES, []);

        if (in_array(static::CORE_NAMESPACE, $coreNamespaces, true)) {
            $output->writeln(sprintf('<info>✓</info> core namespace "%s" is registered', static::CORE_NAMESPACE));

            return;
        }

        $this->failures[] = sprintf(
            'Core namespace "%s" is NOT registered. Add it to KernelConstants::CORE_NAMESPACES in config/Shared/config_default.php — without it Spryker cannot resolve any of this package\'s classes.',
            static::CORE_NAMESPACE,
        );
    }

    /**
     * Class existence only — whether a project actually registered these is not visible from Zed. A
     * missing class means a broken install; a present class means "nothing is stopping you from
     * registering it".
     *
     * The Yves-layer plugins are deliberately NOT checked here: Spryker forbids a Zed file from
     * referencing the Yves namespace at all, and they ship in this same package anyway, so their
     * existence is already implied by the core-namespace check passing.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkPluginClasses(OutputInterface $output): void
    {
        $requiredClasses = [
            'permission plugin' => SubmitSearchFeedbackTicketPermissionPlugin::class,
            'search feedback client' => SearchFeedbackClient::class,
            'replay permission plugin' => ViewSearchFeedbackTicketReplayPermissionPlugin::class,
            'snapshot result formatter plugin' => SearchFeedbackSnapshotResultFormatterPlugin::class,
            'replay-capable search decorator' => ReplayCapableSearch::class,
        ];

        foreach ($requiredClasses as $label => $className) {
            if (class_exists($className)) {
                $output->writeln(sprintf('<info>✓</info> %s class is loadable', $label));

                continue;
            }

            $this->failures[] = sprintf('The %s (%s) could not be autoloaded.', $label, $className);
        }
    }

    /**
     * A real DB round trip through the Facade — the migration-status analog of the sibling packages'
     * search-engine-reachability checks. Zero tickets is completely normal on a fresh install (unlike, say,
     * search-ranking's zero-active-metrics check, which flags a real functional gap) — a plain success
     * line either way, the failure case here is the table/connection not existing at all.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkTicketTable(OutputInterface $output): void
    {
        try {
            $ticketCount = count($this->getFacade()->getTicketCollection()->getTickets());
        } catch (Throwable $exception) {
            $this->failures[] = sprintf(
                'Could not read the ticket table: %s. Run `vendor/bin/console propel:diff` + `vendor/bin/console propel:migrate` (README step 7, not propel:sql:insert — that reapplies the full schema dump).',
                $exception->getMessage(),
            );

            return;
        }

        $output->writeln(sprintf('<info>✓</info> ticket table reachable (%d ticket(s) so far)', $ticketCount));
    }

    /**
     * Zed navigation has no glob auto-discovery for `vendor/spryker-community/*`, so a project copies this
     * package's own `<search-feedback-gui>` block into `config/Zed/navigation.xml` by hand — and a page added by a
     * later version of this package is easy to miss on upgrade. Neither omission errors: the entry is
     * simply absent from the sidebar, and a stale navigation cache hides a correct copy just as
     * completely as never copying it at all.
     *
     * The expected page keys are read from this package's OWN navigation.xml rather than hardcoded here,
     * so this check cannot drift from what the package actually ships.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkNavigationRegistered(OutputInterface $output): void
    {
        $expectedPageKeys = $this->readOwnNavigationPageKeys();
        $effectiveNavigation = $this->readEffectiveNavigation();

        if ($expectedPageKeys === [] || $effectiveNavigation === null) {
            $this->warnings[] = 'Could not compare this package\'s navigation entries against the project\'s own (neither the built navigation cache nor config/Zed/navigation.xml was readable). Confirm by hand that this package\'s pages appear in the Zed sidebar.';

            return;
        }

        [$sourceLabel, $registeredPageKeys] = $effectiveNavigation;
        $missingPageKeys = array_values(array_diff($expectedPageKeys, $registeredPageKeys));

        if ($missingPageKeys === []) {
            $output->writeln(sprintf('<info>✓</info> all %d navigation entries are registered (checked against %s)', count($expectedPageKeys), $sourceLabel));

            return;
        }

        $this->failures[] = sprintf(
            'These navigation entries are missing from %s: %s. First run "vendor/bin/console navigation:cache:remove && vendor/bin/console navigation:build-cache" — a stale cache hides a correct configuration just as completely, and is the cheaper cause to rule out. If they are still missing after that, copy the <search-feedback-gui> block from this package\'s own src/SprykerCommunity/Zed/SearchFeedbackGui/Communication/navigation.xml into config/Zed/navigation.xml (README step 7). A missing entry never errors — the page simply cannot be reached from the sidebar.',
            $sourceLabel,
            implode(', ', $missingPageKeys),
        );
    }

    /**
     * Zed access is deny-by-default outside a matching ACL rule, and this package ships no ACL fixture data
     * (README step 11) — so who can reach its pages is entirely up to the adopter. Two very different
     * installations land here:
     *
     * A default Spryker install needs nothing done: `root_role` carries a total wildcard and every
     * installer user sits in `root_group`, so the pages work the moment the package is installed. An
     * installation running real restricted back-office roles is the opposite — those roles reach nothing
     * here until somebody adds a rule, and the failure is quiet, because
     * {@see \Spryker\Zed\Acl\Communication\Plugin\Navigation\AclNavigationItemFilterPlugin} filters the
     * entry out of the sidebar rather than 403ing. To that user the feature is simply absent, which looks
     * identical to the package never having been installed.
     *
     * A WARNING at most, and worded as something to confirm rather than fix: keeping these pages to
     * root-style admins is a perfectly ordinary choice, and this command cannot know which roles an adopter
     * MEANT to grant. It only reports the one state worth a second look — restricted roles exist, and not
     * one of them has a rule for this package's modules.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkBackOfficeAccess(OutputInterface $output): void
    {
        $moduleNames = $this->readOwnNavigationModuleNames();

        if ($moduleNames === []) {
            $this->warnings[] = 'Could not read this package\'s own navigation.xml, so back-office access could not be checked. Confirm by hand that the Zed roles which should see the Search Feedback pages can actually reach them.';

            return;
        }

        $diagnosisTransfer = $this->getFactory()->createBackOfficeAccessAnalyzer()->analyze($moduleNames);
        $restrictedRoleCount = $diagnosisTransfer->getRestrictedRoleCountOrFail();

        if ($restrictedRoleCount === 0) {
            $output->writeln(sprintf(
                '<info>✓</info> all %d back-office role(s) have unrestricted access, so this package\'s Zed pages need no ACL rule',
                $diagnosisTransfer->getUnrestrictedRoleCountOrFail(),
            ));

            return;
        }

        $restrictedRoleWithAccessCount = $diagnosisTransfer->getRestrictedRoleWithAccessCountOrFail();

        if ($restrictedRoleWithAccessCount > 0) {
            $output->writeln(sprintf(
                '<info>✓</info> %d of %d restricted back-office role(s) have an ACL rule for %s',
                $restrictedRoleWithAccessCount,
                $restrictedRoleCount,
                implode('/', $moduleNames),
            ));

            return;
        }

        $this->warnings[] = sprintf(
            'This project has %d restricted back-office role(s) and none of them has an ACL rule for %s, so only unrestricted (root-style) admins can reach this package\'s Zed pages — for everybody else the sidebar entry is filtered out entirely, which looks the same as the package not being installed. If that is intended, nothing to do. If a restricted role should see Search Feedback, add a rule for it in the Zed ACL Gui (Maintenance > Users & Rights > Roles); README step 11 also covers splitting status changes off from read-only access.',
            $restrictedRoleCount,
            implode('/', $moduleNames),
        );
    }

    /**
     * README step 11 (frozen replay) is optional, so a missing wire-up is a WARNING, never a failure — same
     * posture as {@see checkBackOfficeAccess()}. But of the four registrations that step asks for, this is
     * the one this command can actually see: the project-level `Pyz\Client\SearchElasticsearch\
     * SearchElasticsearchFactory` override that wraps the real `Search` in {@see ReplayCapableSearch}. Miss
     * it and "View SRP" on a ticket with a snapshot silently falls back to a live search instead of erroring
     * — the exact drift this feature exists to close, and nothing in any log says so.
     *
     * The other three registrations from step 11 are NOT checkable from here: the Client
     * `CATALOG_SEARCH_RESULT_FORMATTER_PLUGINS` and both Permission DependencyProviders are DI wiring this
     * command has no way to introspect from Zed's own container (same blind spot as every other
     * DependencyProvider registration in this file — only class loadability is checked, above), and the
     * Yves `EventDispatcherDependencyProvider` entry is a Yves-container concern entirely, closed by the
     * Yves counterpart instead (see the final output block).
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkFrozenReplayWiring(OutputInterface $output): void
    {
        $overrideFilePath = $this->getSearchElasticsearchFactoryOverrideFilePath();

        if (!is_readable($overrideFilePath)) {
            $this->warnings[] = sprintf(
                'Frozen replay is not wired up: no project-level %s override exists (README step 11). "View SRP" on a ticket with a snapshot will silently run a live search instead of replaying it — no error, just drift. Skip this if you intentionally do not use frozen replay.',
                static::SEARCH_ELASTICSEARCH_FACTORY_OVERRIDE_RELATIVE_PATH,
            );

            return;
        }

        $overrideFileContents = (string)file_get_contents($overrideFilePath);

        if (!str_contains($overrideFileContents, 'ReplayCapableSearch')) {
            $this->warnings[] = sprintf(
                'Frozen replay is not wired up: %s exists but does not reference ReplayCapableSearch, so createSearchClient() still returns the plain live Search (README step 11). "View SRP" on a ticket with a snapshot will silently run a live search instead of replaying it.',
                static::SEARCH_ELASTICSEARCH_FACTORY_OVERRIDE_RELATIVE_PATH,
            );

            return;
        }

        $output->writeln('<info>✓</info> project-level SearchElasticsearchFactory override wraps Search in ReplayCapableSearch (frozen replay wired up)');
    }

    /**
     * A real DB round trip, same posture as {@see checkTicketTable()}, but at the schema level: catches a
     * project that installed a version of this package from before the `LONGVARCHAR` → `CLOB` fix and never
     * re-migrated. `LONGVARCHAR` maps to plain MySQL `TEXT` (a 64KB cap) in Propel's own MySQL platform, not
     * `LONGTEXT` — confirmed live: a real captured Elasticsearch response for a full page of products
     * routinely exceeds that, and the truncation surfaces as `SQLSTATE[22001]: String data, right
     * truncated` on ticket submission, not on capture. Warning, not a failure, same reasoning as
     * {@see checkFrozenReplayWiring()}: the table exists unconditionally (it is part of the base schema),
     * but only actually gets written to once frozen replay is wired up, which is optional.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkSnapshotColumnTypes(OutputInterface $output): void
    {
        try {
            $columnTypesByName = $this->readSnapshotColumnTypes();
        } catch (Throwable $exception) {
            $this->warnings[] = sprintf(
                'Could not read %s\'s column types to confirm frozen-replay snapshots won\'t be truncated: %s.',
                static::SNAPSHOT_TABLE_NAME,
                $exception->getMessage(),
            );

            return;
        }

        $badColumnNames = [];

        foreach (static::SNAPSHOT_LONGTEXT_COLUMN_NAMES as $columnName) {
            $columnType = $columnTypesByName[$columnName] ?? null;

            if ($columnType !== null && !str_starts_with($columnType, 'longtext')) {
                $badColumnNames[$columnName] = $columnType;
            }
        }

        if ($badColumnNames === []) {
            $output->writeln(sprintf('<info>✓</info> %s\'s JSON-blob columns are LONGTEXT (won\'t truncate a real captured response)', static::SNAPSHOT_TABLE_NAME));

            return;
        }

        $this->warnings[] = sprintf(
            'These columns on %s are not LONGTEXT and will silently truncate a real captured snapshot: %s. Your project installed this package from before the LONGVARCHAR->CLOB schema fix — run `vendor/bin/console propel:diff` + `vendor/bin/console propel:migrate` to pick up the corrected column type (not propel:sql:insert — that reapplies the full schema dump).',
            static::SNAPSHOT_TABLE_NAME,
            implode(', ', array_map(fn (string $columnName, string $columnType): string => sprintf('%s (%s)', $columnName, $columnType), array_keys($badColumnNames), $badColumnNames)),
        );
    }

    /**
     * Isolated as its own method so a test can stub the connection lookup instead of needing a real
     * database — same seam-for-testability reasoning as {@see getSearchElasticsearchFactoryOverrideFilePath()}.
     *
     * @throws \RuntimeException The query against the snapshot table did not return a statement.
     *
     * @return array<string, string> Column name => lowercased MySQL type.
     */
    protected function readSnapshotColumnTypes(): array
    {
        $connection = Propel::getConnection('zed');
        $statement = $connection->query(sprintf('SHOW FULL COLUMNS FROM `%s`', static::SNAPSHOT_TABLE_NAME));

        if (!($statement instanceof PDOStatement)) {
            throw new RuntimeException(sprintf('Query against %s did not return a statement.', static::SNAPSHOT_TABLE_NAME));
        }

        $columnTypesByName = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columnTypesByName[$row['Field']] = strtolower((string)$row['Type']);
        }

        return $columnTypesByName;
    }

    /**
     * A permission plugin being registered in `Pyz\{Client,Zed}\Permission\PermissionDependencyProvider`
     * does not mean Spryker knows about it yet — that only happens once it's synced into `spy_permission`,
     * a one-time manual step (`/permission/index/sync` in Zed, or the "Sync permissions" link under
     * Maintenance) that is NOT part of `propel:migrate` or any other install command. Confirmed live: skip
     * it and the Company Role create/edit page throws a hard `Undefined array key "<PluginKey>"` for
     * *every* company role, not just ones that would hold this permission — that page always evaluates
     * every registered permission plugin against `spy_permission`.
     *
     * Uses {@see \SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface::findPermissionByKey()},
     * a plain DB lookup — deliberately NOT `PermissionFacade::findMergedRegisteredNonInfrastructuralPermissions()`,
     * which is the exact method that throws the warning above when a plugin isn't yet synced, so calling it
     * here would crash the very check meant to catch that state.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkPermissionsSynced(OutputInterface $output): void
    {
        $unsyncedPermissionKeys = [];

        foreach (static::PERMISSION_KEYS as $permissionKey) {
            if ($this->getPermissionFacade()->findPermissionByKey($permissionKey) === null) {
                $unsyncedPermissionKeys[] = $permissionKey;
            }
        }

        if ($unsyncedPermissionKeys === []) {
            $output->writeln(sprintf('<info>✓</info> all %d permission plugin(s) are synced into spy_permission', count(static::PERMISSION_KEYS)));

            return;
        }

        $this->warnings[] = sprintf(
            'These permission plugins are registered in code but NOT synced into spy_permission yet: %s. Visit /permission/index/sync in Zed once (or click "Sync permissions" under Maintenance) — until then, granting them via the Company Role GUI is impossible, and that GUI\'s create/edit page throws a hard error for every company role, not just ones that would hold these permissions.',
            implode(', ', $unsyncedPermissionKeys),
        );
    }

    /**
     * Isolated as its own method (rather than an inline `$this->getFactory()->getPermissionFacade()` call)
     * so a test can override it directly instead of needing to mock the whole
     * `SearchFeedbackCommunicationFactory` — same seam-for-testability reasoning as
     * {@see getSearchElasticsearchFactoryOverrideFilePath()}.
     */
    protected function getPermissionFacade(): SearchFeedbackToPermissionFacadeInterface
    {
        return $this->getFactory()->getPermissionFacade();
    }

    /**
     * Isolated as its own method (rather than inlined in {@see checkFrozenReplayWiring()}) so a test can
     * override it to point at a fixture file instead of this host shop's real
     * `src/Pyz/Client/SearchElasticsearch/SearchElasticsearchFactory.php` — unlike the ticket-table check,
     * there is no Facade seam to mock a filesystem read through.
     */
    protected function getSearchElasticsearchFactoryOverrideFilePath(): string
    {
        return APPLICATION_ROOT_DIR . static::SEARCH_ELASTICSEARCH_FACTORY_OVERRIDE_RELATIVE_PATH;
    }

    /**
     * Read from this package's OWN navigation.xml rather than hardcoded, same as the page-key check below,
     * so a module added by a later version cannot silently fall out of this check.
     *
     * @return array<string>
     */
    protected function readOwnNavigationModuleNames(): array
    {
        $ownNavigationXml = $this->loadXml(__DIR__ . static::OWN_NAVIGATION_XML_RELATIVE_PATH);

        if ($ownNavigationXml === null) {
            return [];
        }

        $moduleNames = [];

        foreach ($ownNavigationXml->xpath('//bundle') ?: [] as $bundleElement) {
            $moduleNames[(string)$bundleElement] = true;
        }

        return array_keys($moduleNames);
    }

    /**
     * Every page key this package's own navigation.xml declares — the root entry plus each `<pages>`
     * child, including the ones marked `<visible>0</visible>` (invisible still means routable, and a
     * project that skipped them gets a dead link from the visible pages that point at them).
     *
     * @return array<string>
     */
    protected function readOwnNavigationPageKeys(): array
    {
        $ownNavigationXml = $this->loadXml(__DIR__ . static::OWN_NAVIGATION_XML_RELATIVE_PATH);

        if ($ownNavigationXml === null) {
            return [];
        }

        $pageKeys = [];

        foreach ($ownNavigationXml->children() as $rootEntry) {
            $pageKeys[] = $rootEntry->getName();

            foreach ($rootEntry->pages->children() as $page) {
                $pageKeys[] = $page->getName();
            }
        }

        return $pageKeys;
    }

    /**
     * Prefers the BUILT navigation cache over the project's raw XML, because the cache is what Zed
     * actually renders from — a correct copy that was never followed by a cache rebuild is a real, and
     * easy to miss, failure mode. Falls back to the raw XML when no cache has been built.
     *
     * @return array{0: string, 1: array<string>}|null
     */
    protected function readEffectiveNavigation(): ?array
    {
        $cacheFilePath = APPLICATION_ROOT_DIR . '/src/Generated/Zed/Navigation/codeBucket/navigation.cache';

        if (is_readable($cacheFilePath)) {
            $cachedNavigation = json_decode((string)file_get_contents($cacheFilePath), true);

            if (is_array($cachedNavigation)) {
                return ['the built navigation cache', $this->collectCachedPageKeys($cachedNavigation)];
            }
        }

        $projectPageKeys = $this->readProjectNavigationPageKeys();

        return $projectPageKeys === null ? null : ['config/Zed/navigation.xml', $projectPageKeys];
    }

    /**
     * @return array<string>|null
     */
    protected function readProjectNavigationPageKeys(): ?array
    {
        $projectNavigationXml = $this->loadXml(APPLICATION_ROOT_DIR . '/config/Zed/navigation.xml');

        if ($projectNavigationXml === null) {
            return null;
        }

        $pageKeys = [];

        foreach ($projectNavigationXml->xpath('//*') ?: [] as $element) {
            $pageKeys[] = $element->getName();
        }

        return $pageKeys;
    }

    /**
     * @param array<string, mixed> $cachedNavigation
     *
     * @return array<string>
     */
    protected function collectCachedPageKeys(array $cachedNavigation): array
    {
        $pageKeys = [];

        foreach ($cachedNavigation as $pageKey => $page) {
            $pageKeys[] = (string)$pageKey;

            if (!is_array($page) || !is_array($page['pages'] ?? null)) {
                continue;
            }

            $pageKeys = array_merge($pageKeys, $this->collectCachedPageKeys($page['pages']));
        }

        return $pageKeys;
    }

    /**
     * @param string $filePath
     */
    protected function loadXml(string $filePath): ?SimpleXMLElement
    {
        if (!is_readable($filePath)) {
            return null;
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string)file_get_contents($filePath));
        libxml_use_internal_errors($previousUseInternalErrors);

        return $xml === false ? null : $xml;
    }

    /**
     * The Zed catalog and the strings the GUI actually renders drift apart silently, in both directions,
     * because the keys ARE the English text: a key missing from the catalog still renders correct English
     * and only shows up as untranslated in a non-English Zed. Nothing else notices, which is how this
     * package's own catalog fell behind its GUI once already.
     *
     * Scans this package's own Zed sources for `|trans` keys and asserts each one is in the shipped
     * catalog. Deliberately one-directional: a key that looks unused to this scan may still be reached
     * through addSuccessMessage(), a widget_title, a table header or a form label, all of which are
     * translated at render time, so an unused-looking entry is never reported as a problem.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkZedTranslationCatalogComplete(OutputInterface $output): void
    {
        $usedKeys = $this->collectUsedZedTranslationKeys();
        $catalogKeys = $this->readZedTranslationCatalogKeys(static::TRANSLATION_REFERENCE_LOCALE);

        if ($usedKeys === [] || $catalogKeys === null) {
            $this->warnings[] = 'Could not compare this package\'s Zed translation catalog against the strings its GUI uses (sources or catalog unreadable). Nothing to act on unless you are working on the package itself.';

            return;
        }

        $missingKeys = array_values(array_diff($usedKeys, $catalogKeys));

        if ($missingKeys === []) {
            $output->writeln(sprintf('<info>✓</info> all %d Zed GUI strings are present in the translation catalog', count($usedKeys)));

            return;
        }

        $this->failures[] = sprintf(
            '%d Zed GUI string(s) are missing from data/translation/Zed/%s.csv and will render untranslated in any non-English Zed: "%s". This is a defect in the package itself, not in your project setup.',
            count($missingKeys),
            static::TRANSLATION_REFERENCE_LOCALE,
            implode('", "', array_slice($missingKeys, 0, 8)) . (count($missingKeys) > 8 ? '", ...' : ''),
        );
    }

    /**
     * @return array<string>
     */
    protected function collectUsedZedTranslationKeys(): array
    {
        $zedSourcePath = __DIR__ . static::PACKAGE_ROOT_RELATIVE_PATH . '/src/SprykerCommunity/Zed';

        if (!is_dir($zedSourcePath)) {
            return [];
        }

        $keys = [];
        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($zedSourcePath, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($directoryIterator as $fileInfo) {
            if (!$fileInfo->isFile() || !in_array(strtolower($fileInfo->getExtension()), ['twig', 'php'], true)) {
                continue;
            }

            $keys = array_merge($keys, $this->extractTranslationKeys((string)file_get_contents($fileInfo->getPathname())));
        }

        return array_values(array_unique($keys));
    }

    /**
     * Skips anything interpolated (`~`, `{{ }}`) — those are built at runtime and cannot be matched
     * against a static catalog.
     *
     * @param string $source
     *
     * @return array<string>
     */
    protected function extractTranslationKeys(string $source): array
    {
        $keys = [];

        foreach ([static::PATTERN_TWIG_TRANS, static::PATTERN_PHP_TRANS] as $pattern) {
            preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $key = str_replace(['\\\'', '\\"'], ['\'', '"'], $match[2]);

                if (str_contains($key, '{') || str_contains($key, '~') || str_starts_with($key, '/')) {
                    continue;
                }

                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param string $locale
     *
     * @return array<string>|null
     */
    protected function readZedTranslationCatalogKeys(string $locale): ?array
    {
        $catalogPath = sprintf('%s%s/data/translation/Zed/%s.csv', __DIR__, static::PACKAGE_ROOT_RELATIVE_PATH, $locale);

        if (!is_readable($catalogPath)) {
            return null;
        }

        $handle = fopen($catalogPath, 'r');

        if ($handle === false) {
            return null;
        }

        $keys = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (!isset($row[0]) || trim((string)$row[0]) === '') {
                continue;
            }

            $keys[] = (string)$row[0];
        }

        fclose($handle);

        return $keys;
    }
}
