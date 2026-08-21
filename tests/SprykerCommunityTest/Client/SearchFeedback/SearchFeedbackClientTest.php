<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToSessionClientInterface;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;
use SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface;
use SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotRestorerPluginInterface;
use SprykerCommunity\Client\SearchFeedback\Search\SearchFeedbackSnapshotContext;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClient;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackDependencyProvider;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory;

/**
 * `submitTicket()` really delegates to the factory-built stub and returns exactly what it returns,
 * unmodified — `SearchFeedbackStubTest` already covers the stub's own HTTP-call behavior, so this test's
 * job is only the one hop above it. `getTicketSrpSnapshot()` is covered the same way, same reasoning.
 * `consumeSnapshot()` additionally covers its own real branching (null vs. transfer-building) on top of
 * that one-hop delegation, since — unlike the other two — it does something with what `SnapshotContext`
 * returns rather than just passing it through.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group SearchFeedbackClientTest
 * @group Portable
 */
class SearchFeedbackClientTest extends Unit
{
    public function testSubmitTicketDelegatesToTheFactoryBuiltStubAndReturnsItsResponseUnmodified(): void
    {
        // Arrange
        $requestTransfer = (new SearchFeedbackTicketRequestTransfer())->setTopic('relevance');
        $responseTransfer = (new SearchFeedbackTicketResponseTransfer())->setIsSuccess(true);

        $zedRequestClientMock = $this->createMock(SearchFeedbackToZedRequestInterface::class);
        $zedRequestClientMock->method('call')
            ->with('/search-feedback/gateway/submit-ticket', $requestTransfer)
            ->willReturn($responseTransfer);

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST, $zedRequestClientMock);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert
        $this->assertSame($responseTransfer, $client->submitTicket($requestTransfer));
    }

    public function testHasTermVectorSnapshotProviderPluginReturnsFalseWhenNoneRegistered(): void
    {
        // Arrange
        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS, fn () => []);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert
        $this->assertFalse($client->hasTermVectorSnapshotProviderPlugin());
    }

    public function testHasTermVectorSnapshotProviderPluginReturnsTrueWhenOneIsRegistered(): void
    {
        // Arrange
        $providerPluginMock = $this->createMock(TermVectorSnapshotProviderPluginInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS, fn () => [$providerPluginMock]);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert
        $this->assertTrue($client->hasTermVectorSnapshotProviderPlugin());
    }

    public function testRestoreTermVectorSnapshotIsANoOpWhenNoRestorerPluginIsRegistered(): void
    {
        // Arrange
        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_RESTORER_PLUGINS, fn () => []);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert — no exception is the whole assertion; nothing registered means nothing to call.
        $client->restoreTermVectorSnapshot('{"relevanceWeight":0.5}');
    }

    public function testRestoreTermVectorSnapshotCallsEveryRegisteredRestorerPluginWithTheGivenSnapshot(): void
    {
        // Arrange
        $firstRestorerPluginMock = $this->createMock(TermVectorSnapshotRestorerPluginInterface::class);
        $firstRestorerPluginMock->expects($this->once())->method('restoreTermVectorSnapshot')->with('{"relevanceWeight":0.5}');

        $secondRestorerPluginMock = $this->createMock(TermVectorSnapshotRestorerPluginInterface::class);
        $secondRestorerPluginMock->expects($this->once())->method('restoreTermVectorSnapshot')->with('{"relevanceWeight":0.5}');

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_RESTORER_PLUGINS, fn () => [$firstRestorerPluginMock, $secondRestorerPluginMock]);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act
        $client->restoreTermVectorSnapshot('{"relevanceWeight":0.5}');
    }

    public function testGetTicketSrpSnapshotDelegatesToTheFactoryBuiltStubAndReturnsItsResponseUnmodified(): void
    {
        // Arrange
        $requestTransfer = (new SearchFeedbackTicketSrpSnapshotRequestTransfer())->setIdSearchFeedbackTicket(1);
        $responseTransfer = (new SearchFeedbackTicketSrpSnapshotResponseTransfer())->setIsFound(true);

        $zedRequestClientMock = $this->createMock(SearchFeedbackToZedRequestInterface::class);
        $zedRequestClientMock->method('call')
            ->with('/search-feedback/gateway/get-ticket-srp-snapshot', $requestTransfer)
            ->willReturn($responseTransfer);

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST, $zedRequestClientMock);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert
        $this->assertSame($responseTransfer, $client->getTicketSrpSnapshot($requestTransfer));
    }

    public function testConsumeSnapshotReturnsNullWhenNoSnapshotWasStoredForTheToken(): void
    {
        // Arrange
        $client = $this->buildClientWithSnapshotSessionValue(null);

        // Act & Assert
        $this->assertNull($client->consumeSnapshot('some-token'));
    }

    public function testConsumeSnapshotBuildsATransferFromTheStoredSnapshot(): void
    {
        // Arrange
        $client = $this->buildClientWithSnapshotSessionValue([
            'rawResponse' => '{"hits":[]}',
            'queryDsl' => '{"query":{}}',
            'requestParameters' => '{"q":"cable"}',
            'hasTermVectorSnapshot' => true,
            'termVectorSnapshot' => '{"terms":[]}',
        ]);

        // Act
        $result = $client->consumeSnapshot('some-token');

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('{"hits":[]}', $result->getRawResponse());
        $this->assertSame('{"query":{}}', $result->getQueryDsl());
        $this->assertSame('{"q":"cable"}', $result->getRequestParameters());
        $this->assertTrue($result->getHasTermVectorSnapshot());
        $this->assertSame('{"terms":[]}', $result->getTermVectorSnapshot());
    }

    /**
     * @param array<string, mixed>|null $storedSnapshot
     */
    protected function buildClientWithSnapshotSessionValue(?array $storedSnapshot): SearchFeedbackClient
    {
        $sessionClientMock = $this->createMock(SearchFeedbackToSessionClientInterface::class);
        $sessionClientMock->method('get')->willReturn($storedSnapshot);

        $container = new Container();
        $container->set(
            SearchFeedbackDependencyProvider::SNAPSHOT_CONTEXT,
            fn () => new SearchFeedbackSnapshotContext($sessionClientMock),
        );

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        return $client;
    }
}
