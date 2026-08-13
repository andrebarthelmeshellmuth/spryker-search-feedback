<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Communication\Console;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use RuntimeException;
use SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacade;
use SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unlike the sibling `search-debug`/`search-ranking` packages' equivalent commands, every check this
 * console runs is either pure (`class_exists()`, `Config::get()`) or goes through the mockable Facade —
 * there is no real-infrastructure-dependent check (no search engine to reach), so this test needs no live
 * service and asserts fully offline.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Communication
 * @group Console
 * @group SearchFeedbackCheckInstallationConsoleTest
 * @group NeedsProject
 */
class SearchFeedbackCheckInstallationConsoleTest extends Unit
{
    public function testSucceedsAndReportsEveryCheckWhenTheTicketTableIsReachable(): void
    {
        // Arrange
        $commandTester = $this->createCommandTester($this->createTicketCollection(3));

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('core namespace "SprykerCommunity" is registered', $commandTester->getDisplay());
        $this->assertStringContainsString('permission plugin class is loadable', $commandTester->getDisplay());
        $this->assertStringContainsString('search feedback client class is loadable', $commandTester->getDisplay());
        $this->assertStringContainsString('ticket table reachable (3 ticket(s) so far)', $commandTester->getDisplay());
        $this->assertStringContainsString('Everything checkable from the CLI is in place.', $commandTester->getDisplay());
        $this->assertStringContainsString('router:cache:warm-up:backend-gateway', $commandTester->getDisplay());
    }

    /**
     * Zero tickets is a plain success, never even a warning — completely normal on a fresh install, unlike
     * e.g. search-ranking's zero-active-metrics check, which flags a real functional gap.
     */
    public function testSucceedsWithNoWarningWhenThereAreZeroTicketsYet(): void
    {
        // Arrange
        $commandTester = $this->createCommandTester($this->createTicketCollection(0));

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('ticket table reachable (0 ticket(s) so far)', $commandTester->getDisplay());
        $this->assertStringContainsString('Everything checkable from the CLI is in place.', $commandTester->getDisplay());
    }

    /**
     * README step 11 (frozen replay) is optional, so a missing override is a WARNING — the command still
     * exits {@see SearchFeedbackCheckInstallationConsole::CODE_SUCCESS}.
     */
    public function testWarnsWhenTheSearchElasticsearchFactoryOverrideFileDoesNotExist(): void
    {
        // Arrange — a path nothing ever creates, standing in for a project that skipped step 11 entirely.
        $commandTester = $this->createCommandTesterWithOverrideFilePath(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.php');

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('Frozen replay is not wired up: no project-level', $commandTester->getDisplay());
        $this->assertStringContainsString('README step 11', $commandTester->getDisplay());
        $this->assertStringNotContainsString('frozen replay wired up', $commandTester->getDisplay());
    }

    /**
     * The override file exists — a project DID create it — but never actually wraps `Search` in
     * `ReplayCapableSearch`, so `createSearchClient()` still returns the plain live client. Same silent
     * failure the file-missing case reports, worded differently because the remedy is different (fix the
     * class body, not create the file).
     */
    public function testWarnsWhenTheSearchElasticsearchFactoryOverrideFileDoesNotReferenceReplayCapableSearch(): void
    {
        // Arrange
        $overrideFilePath = $this->createOverrideFileFixture('<?php class SearchElasticsearchFactory {}');
        $commandTester = $this->createCommandTesterWithOverrideFilePath($overrideFilePath);

        try {
            // Act
            $exitCode = $commandTester->execute([]);

            // Assert
            $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
            $this->assertStringContainsString('exists but does not reference ReplayCapableSearch', $commandTester->getDisplay());
            $this->assertStringNotContainsString('frozen replay wired up', $commandTester->getDisplay());
        } finally {
            unlink($overrideFilePath);
        }
    }

    public function testSucceedsWithoutWarningWhenTheSearchElasticsearchFactoryOverrideWrapsReplayCapableSearch(): void
    {
        // Arrange
        $overrideFilePath = $this->createOverrideFileFixture('<?php class SearchElasticsearchFactory { public function createSearchClient() { return new ReplayCapableSearch(); } }');
        $commandTester = $this->createCommandTesterWithOverrideFilePath($overrideFilePath);

        try {
            // Act
            $exitCode = $commandTester->execute([]);

            // Assert
            $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
            $this->assertStringContainsString('frozen replay wired up', $commandTester->getDisplay());
            $this->assertStringNotContainsString('Frozen replay is not wired up', $commandTester->getDisplay());
        } finally {
            unlink($overrideFilePath);
        }
    }

    public function testFailsAndNamesTheRemedyWhenTheTicketTableIsUnreachable(): void
    {
        // Arrange
        $facadeMock = $this->getMockBuilder(SearchFeedbackFacade::class)
            ->onlyMethods(['getTicketCollection'])
            ->getMock();
        $facadeMock->method('getTicketCollection')->willThrowException(new RuntimeException('SQLSTATE[42S02]: Base table or view not found'));

        $console = new SearchFeedbackCheckInstallationConsole();
        $console->setFacade($facadeMock);

        $application = new Application();
        $application->add($console);
        $commandTester = new CommandTester($application->find(SearchFeedbackCheckInstallationConsole::COMMAND_NAME));

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_ERROR, $exitCode);
        $this->assertStringContainsString('Could not read the ticket table', $commandTester->getDisplay());
        $this->assertStringContainsString('propel:diff', $commandTester->getDisplay());
        $this->assertStringContainsString('propel:migrate', $commandTester->getDisplay());
    }

    /**
     * @param int $count
     */
    protected function createTicketCollection(int $count): SearchFeedbackTicketCollectionTransfer
    {
        $tickets = new ArrayObject();

        for ($i = 0; $i < $count; $i++) {
            $tickets->append((new SearchFeedbackTicketTransfer())->setIdSearchFeedbackTicket($i));
        }

        return (new SearchFeedbackTicketCollectionTransfer())->setTickets($tickets);
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer $ticketCollection
     */
    protected function createCommandTester(SearchFeedbackTicketCollectionTransfer $ticketCollection): CommandTester
    {
        $facadeMock = $this->getMockBuilder(SearchFeedbackFacade::class)
            ->onlyMethods(['getTicketCollection'])
            ->getMock();
        $facadeMock->method('getTicketCollection')->willReturn($ticketCollection);

        $console = new SearchFeedbackCheckInstallationConsole();
        $console->setFacade($facadeMock);

        $application = new Application();
        $application->add($console);

        $command = $application->find(SearchFeedbackCheckInstallationConsole::COMMAND_NAME);

        return new CommandTester($command);
    }

    /**
     * Writes a throwaway PHP file standing in for a project's
     * `src/Pyz/Client/SearchElasticsearch/SearchElasticsearchFactory.php` override — the console only ever
     * reads this file's contents with `file_get_contents()` via
     * {@see SearchFeedbackCheckInstallationConsole::getSearchElasticsearchFactoryOverrideFilePath()}, it
     * never includes/parses it, so the contents don't need to be autoload-safe PHP, just contain (or not
     * contain) the literal string `ReplayCapableSearch`.
     */
    protected function createOverrideFileFixture(string $contents): string
    {
        $overrideFilePath = tempnam(sys_get_temp_dir(), 'search-elasticsearch-factory-override-fixture-');

        file_put_contents($overrideFilePath, $contents);

        return $overrideFilePath;
    }

    /**
     * A real ticket collection and application/command wiring, same as {@see createCommandTester()}, but
     * with an anonymous subclass overriding
     * {@see SearchFeedbackCheckInstallationConsole::getSearchElasticsearchFactoryOverrideFilePath()} so the
     * frozen-replay wiring check reads the given fixture path instead of this host shop's real
     * `SearchElasticsearchFactory.php` — there is no Facade seam to mock a filesystem read through, unlike
     * every other check in this console.
     */
    protected function createCommandTesterWithOverrideFilePath(string $overrideFilePath): CommandTester
    {
        $facadeMock = $this->getMockBuilder(SearchFeedbackFacade::class)
            ->onlyMethods(['getTicketCollection'])
            ->getMock();
        $facadeMock->method('getTicketCollection')->willReturn($this->createTicketCollection(1));

        $console = new class ($overrideFilePath) extends SearchFeedbackCheckInstallationConsole {
            public function __construct(protected string $overrideFilePath)
            {
                parent::__construct();
            }

            protected function getSearchElasticsearchFactoryOverrideFilePath(): string
            {
                return $this->overrideFilePath;
            }
        };
        $console->setFacade($facadeMock);

        $application = new Application();
        $application->add($console);

        $command = $application->find(SearchFeedbackCheckInstallationConsole::COMMAND_NAME);

        return new CommandTester($command);
    }
}
