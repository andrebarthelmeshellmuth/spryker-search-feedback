<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Console;

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
        $output->writeln('  - the navigation.xml entry (step 6) and ACL group setup (step 9) — Zed-admin-config concerns,');
        $output->writeln('    not something a class or a DB row can attest to.');
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
}
