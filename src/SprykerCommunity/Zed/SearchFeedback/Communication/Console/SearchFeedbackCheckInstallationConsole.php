<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Console;

use SimpleXMLElement;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClient;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
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
 * template renders correctly (step 5's nested-`<form>` gotcha). Also cannot confirm the navigation.xml
 * entry (step 6) or ACL group setup (step 9) — those are Zed-admin-config concerns, not something a class
 * or a DB row can attest to. It CAN, and does, flag the single most notorious silent-failure point this
 * package's own README calls out by name: the Backend Gateway router needs its OWN cache warmed
 * (step 8) — every other Zed page works fine while ticket submission alone 404s until that runs.
 *
 * Complementary counterpart:
 * {@see \SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController} (the
 * `/search-feedback-widget/check-installation` page) closes exactly the Yves-side gap this command names
 * above — the Twig functions and the submit-ticket route — by running from inside the real Yves DI
 * container. It does not re-check anything this command already covers; run both for a full picture.
 *
 * @method \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacadeInterface getFacade()
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
    public const COMMAND_DESCRIPTION = 'Diagnoses a search-feedback installation: core namespace, plugin classes, and ticket-table reachability.';

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
        $output->writeln('  - ACL group setup (step 9) — a Zed-admin-config concern, not something a class or a DB');
        $output->writeln('    row can attest to. (The navigation.xml entry itself IS checked above.)');
        $output->writeln('  - the Backend Gateway router cache (step 8) — its OWN cache, separate from every other Zed');
        $output->writeln('    page\'s router; run `vendor/bin/console router:cache:warm-up:backend-gateway` if ticket');
        $output->writeln('    submission alone 404s while everything else works.');
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
}
