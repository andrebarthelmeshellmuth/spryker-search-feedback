<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Communication\Console;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use RuntimeException;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;
use SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacade;
use SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;
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

    /**
     * The exact real-world state confirmed live: a project installed this package from before the
     * LONGVARCHAR->CLOB schema fix, so `raw_response` is plain MySQL TEXT (a 64KB cap) instead of
     * LONGTEXT — silently truncates a real captured Elasticsearch response on ticket submission. Optional
     * feature (frozen replay), so a WARNING, not a failure, same posture as the other frozen-replay checks.
     */
    public function testWarnsWhenASnapshotColumnIsNotLongtext(): void
    {
        // Arrange
        $commandTester = $this->createCommandTesterWithColumnTypes([
            'raw_response' => 'text',
            'query_dsl' => 'longtext',
            'request_parameters' => 'longtext',
            'term_vector_snapshot' => 'longtext',
        ]);

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('raw_response (text)', $commandTester->getDisplay());
        $this->assertStringContainsString('propel:diff', $commandTester->getDisplay());
        $this->assertStringContainsString('propel:migrate', $commandTester->getDisplay());
    }

    public function testSucceedsWithoutWarningWhenAllSnapshotColumnsAreLongtext(): void
    {
        // Arrange
        $commandTester = $this->createCommandTesterWithColumnTypes([
            'raw_response' => 'longtext',
            'query_dsl' => 'longtext',
            'request_parameters' => 'longtext',
            'term_vector_snapshot' => 'longtext',
        ]);

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('JSON-blob columns are LONGTEXT', $commandTester->getDisplay());
    }

    /**
     * The exact real-world state confirmed live: a permission plugin registered in
     * `Pyz\{Client,Zed}\Permission\PermissionDependencyProvider` but never synced into `spy_permission` via
     * `/permission/index/sync` — the Company Role GUI throws a hard error for every company role until this
     * is done, not just a missing checkbox. Optional feature, so a warning, not a failure.
     */
    public function testWarnsWhenAPermissionPluginIsNotSyncedIntoSpyPermission(): void
    {
        // Arrange
        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->method('findPermissionByKey')->willReturnCallback(
            fn (string $key): ?PermissionTransfer => $key === ViewSearchFeedbackTicketReplayPermissionPlugin::KEY
                ? null
                : (new PermissionTransfer())->setKey($key),
        );

        $commandTester = $this->createCommandTesterWithPermissionFacade($permissionFacadeMock);

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString(ViewSearchFeedbackTicketReplayPermissionPlugin::KEY, $commandTester->getDisplay());
        $this->assertStringContainsString('/permission/index/sync', $commandTester->getDisplay());
        $this->assertStringNotContainsString(SubmitSearchFeedbackTicketPermissionPlugin::KEY . ' are registered', $commandTester->getDisplay());
    }

    public function testSucceedsWithoutWarningWhenAllPermissionPluginsAreSynced(): void
    {
        // Arrange
        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->method('findPermissionByKey')->willReturnCallback(
            fn (string $key): PermissionTransfer => (new PermissionTransfer())->setKey($key),
        );

        $commandTester = $this->createCommandTesterWithPermissionFacade($permissionFacadeMock);

        // Act
        $exitCode = $commandTester->execute([]);

        // Assert
        $this->assertSame(SearchFeedbackCheckInstallationConsole::CODE_SUCCESS, $exitCode);
        $this->assertStringContainsString('permission plugin(s) are synced into spy_permission', $commandTester->getDisplay());
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

    /**
     * A real ticket collection and application/command wiring, same as {@see createCommandTester()}, but
     * with an anonymous subclass overriding
     * {@see SearchFeedbackCheckInstallationConsole::readSnapshotColumnTypes()} so the column-type check
     * reads the given fixture data instead of this host shop's real database.
     *
     * @param array<string, string> $columnTypesByName
     */
    protected function createCommandTesterWithColumnTypes(array $columnTypesByName): CommandTester
    {
        $facadeMock = $this->getMockBuilder(SearchFeedbackFacade::class)
            ->onlyMethods(['getTicketCollection'])
            ->getMock();
        $facadeMock->method('getTicketCollection')->willReturn($this->createTicketCollection(1));

        $console = new class ($columnTypesByName) extends SearchFeedbackCheckInstallationConsole {
            /**
             * @param array<string, string> $columnTypesByName
             */
            public function __construct(protected array $columnTypesByName)
            {
                parent::__construct();
            }

            /**
             * @return array<string, string>
             */
            protected function readSnapshotColumnTypes(): array
            {
                return $this->columnTypesByName;
            }
        };
        $console->setFacade($facadeMock);

        $application = new Application();
        $application->add($console);

        $command = $application->find(SearchFeedbackCheckInstallationConsole::COMMAND_NAME);

        return new CommandTester($command);
    }

    /**
     * A real ticket collection and application/command wiring, same as {@see createCommandTester()}, but
     * with an anonymous subclass overriding
     * {@see SearchFeedbackCheckInstallationConsole::getPermissionFacade()} so the permission-sync check
     * reads the given fixture instead of this host shop's real Permission facade.
     */
    protected function createCommandTesterWithPermissionFacade(SearchFeedbackToPermissionFacadeInterface $permissionFacade): CommandTester
    {
        $facadeMock = $this->getMockBuilder(SearchFeedbackFacade::class)
            ->onlyMethods(['getTicketCollection'])
            ->getMock();
        $facadeMock->method('getTicketCollection')->willReturn($this->createTicketCollection(1));

        $console = new class ($permissionFacade) extends SearchFeedbackCheckInstallationConsole {
            public function __construct(protected SearchFeedbackToPermissionFacadeInterface $permissionFacade)
            {
                parent::__construct();
            }

            protected function getPermissionFacade(): SearchFeedbackToPermissionFacadeInterface
            {
                return $this->permissionFacade;
            }
        };
        $console->setFacade($facadeMock);

        $application = new Application();
        $application->add($console);

        $command = $application->find(SearchFeedbackCheckInstallationConsole::COMMAND_NAME);

        return new CommandTester($command);
    }
}
