<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Search;

use Codeception\Test\Unit;
use Elastica\Query;
use Elastica\Response;
use Elastica\ResultSet;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\SearchElasticsearch\Search\SearchInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\ResultFormatterPluginInterface;
use SprykerCommunity\Client\SearchFeedback\Search\ReplayCapableSearch;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * Covers the fall-through decision tree (the part with real branches to get wrong) and the
 * reconstruct-from-stored-data path. Does NOT cover Elastica's own Response/Query/DefaultBuilder
 * round-trip fidelity — that's Elastica's own tested behavior, not this class's.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group ReplayCapableSearchTest
 * @group Portable
 */
class ReplayCapableSearchTest extends Unit
{
    public function testFallsThroughToTheDecoratedSearchWhenNoReplayTicketIdIsOnTheRequest(): void
    {
        // Arrange
        $searchQueryMock = $this->createMock(QueryInterface::class);
        $liveResult = new ResultSet(new Response('{}'), Query::create([]), []);

        $decoratedSearchMock = $this->createMock(SearchInterface::class);
        $decoratedSearchMock->expects($this->once())
            ->method('search')
            ->with($searchQueryMock, [], [])
            ->willReturn($liveResult);

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->expects($this->never())->method('getTicketSrpSnapshot');

        $customerClientMock = $this->createMock(CustomerClientInterface::class);

        $replaySearch = new ReplayCapableSearch($decoratedSearchMock, $searchFeedbackClientMock, $customerClientMock);

        // Act & Assert
        $this->assertSame($liveResult, $replaySearch->search($searchQueryMock, [], []));
    }

    public function testFallsThroughToTheDecoratedSearchWhenNoCustomerIsLoggedIn(): void
    {
        // Arrange
        $searchQueryMock = $this->createMock(QueryInterface::class);
        $requestParameters = [SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET => '42'];

        $decoratedSearchMock = $this->createMock(SearchInterface::class);
        $decoratedSearchMock->expects($this->once())->method('search')->willReturn([]);

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->expects($this->never())->method('getTicketSrpSnapshot');

        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn(null);

        $replaySearch = new ReplayCapableSearch($decoratedSearchMock, $searchFeedbackClientMock, $customerClientMock);

        // Act
        $replaySearch->search($searchQueryMock, [], $requestParameters);
    }

    public function testFallsThroughToTheDecoratedSearchWhenZedReportsNoSnapshotFound(): void
    {
        // Arrange
        $searchQueryMock = $this->createMock(QueryInterface::class);
        $requestParameters = [SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET => '42'];

        $decoratedSearchMock = $this->createMock(SearchInterface::class);
        $decoratedSearchMock->expects($this->once())->method('search')->willReturn([]);

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('getTicketSrpSnapshot')
            ->willReturn((new SearchFeedbackTicketSrpSnapshotResponseTransfer())->setIsFound(false));

        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn((new CustomerTransfer())->setCustomerReference('CUST-1'));

        $replaySearch = new ReplayCapableSearch($decoratedSearchMock, $searchFeedbackClientMock, $customerClientMock);

        // Act
        $replaySearch->search($searchQueryMock, [], $requestParameters);
    }

    public function testReplaysTheStoredSnapshotAndReturnsTheRawResultSetWhenNoFormattersArePassed(): void
    {
        // Arrange
        $searchQueryMock = $this->createMock(QueryInterface::class);
        $requestParameters = [SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET => '42'];

        $snapshotTransfer = (new SearchFeedbackTicketSrpSnapshotTransfer())
            ->setRawResponse('{"hits":{"hits":[]}}')
            ->setQueryDsl('{"query":{"match_all":{}}}');

        $decoratedSearchMock = $this->createMock(SearchInterface::class);
        $decoratedSearchMock->expects($this->never())->method('search');

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('getTicketSrpSnapshot')
            ->willReturn(
                (new SearchFeedbackTicketSrpSnapshotResponseTransfer())
                    ->setIsFound(true)
                    ->setSnapshot($snapshotTransfer),
            );

        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn((new CustomerTransfer())->setCustomerReference('CUST-1'));

        $replaySearch = new ReplayCapableSearch($decoratedSearchMock, $searchFeedbackClientMock, $customerClientMock);

        // Act
        $result = $replaySearch->search($searchQueryMock, [], $requestParameters);

        // Assert — a real ResultSet reconstructed purely from the stored strings, no ES call.
        $this->assertInstanceOf(ResultSet::class, $result);
        $this->assertCount(0, $result->getResults());
    }

    public function testReplaysTheStoredSnapshotThroughResultFormattersWhenSomeArePassed(): void
    {
        // Arrange
        $searchQueryMock = $this->createMock(QueryInterface::class);
        $requestParameters = [SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET => '42'];

        $snapshotTransfer = (new SearchFeedbackTicketSrpSnapshotTransfer())
            ->setRawResponse('{"hits":{"hits":[]}}')
            ->setQueryDsl('{"query":{"match_all":{}}}');

        $decoratedSearchMock = $this->createMock(SearchInterface::class);
        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('getTicketSrpSnapshot')
            ->willReturn(
                (new SearchFeedbackTicketSrpSnapshotResponseTransfer())
                    ->setIsFound(true)
                    ->setSnapshot($snapshotTransfer),
            );

        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn((new CustomerTransfer())->setCustomerReference('CUST-1'));

        $resultFormatterMock = $this->createMock(ResultFormatterPluginInterface::class);
        $resultFormatterMock->method('getName')->willReturn('formattedKey');
        $resultFormatterMock->expects($this->once())
            ->method('formatResult')
            ->with($this->isInstanceOf(ResultSet::class), $requestParameters)
            ->willReturn(['formatted' => true]);

        $replaySearch = new ReplayCapableSearch($decoratedSearchMock, $searchFeedbackClientMock, $customerClientMock);

        // Act
        $result = $replaySearch->search($searchQueryMock, [$resultFormatterMock], $requestParameters);

        // Assert
        $this->assertSame(['formattedKey' => ['formatted' => true]], $result);
    }
}
