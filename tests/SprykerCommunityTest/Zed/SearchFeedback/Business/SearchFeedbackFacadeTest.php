<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacade;

/**
 * INTEGRATION TEST, real database — exercises every public Facade method through the real
 * `BusinessFactory` -> `TicketManager` -> `EntityManager`/`Repository` chain, the same round trip a
 * consuming module (or `GatewayController`/`DetailController`) drives via the Locator. Measured gap this
 * closes: `TicketManagerTest` already covers the business-logic branches with mocked collaborators, and
 * `GatewayControllerTest` already proves `submitTicket()` end-to-end, but the other four Facade methods
 * (`replyToTicket`, `changeTicketStatus`, `getTicketCollection`, `findTicketById` — the ones
 * `DetailController`/`IndexController` call) had no coverage AT the Facade's own public boundary before
 * this class — confirmed via `codecept run --coverage-text`, which reported
 * `SearchFeedbackFacade Methods: 20.00% (1/5)` before these tests were added.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Business
 * @group SearchFeedbackFacadeTest
 */
class SearchFeedbackFacadeTest extends Unit
{
    public function testSubmitTicketPersistsARealTicketAndReturnsItOnTheResponse(): void
    {
        // Act
        $responseTransfer = (new SearchFeedbackFacade())->submitTicket($this->createRequestTransfer());

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccess());
        $this->assertSame('chair', $responseTransfer->getTicketOrFail()->getSearchTerm());
    }

    public function testReplyToTicketAppendsARealMessageAndReturnsTheUpdatedThread(): void
    {
        // Arrange
        $facade = new SearchFeedbackFacade();
        $submitResponseTransfer = $facade->submitTicket($this->createRequestTransfer());
        $idSearchFeedbackTicket = $submitResponseTransfer->getTicketOrFail()->getIdSearchFeedbackTicketOrFail();

        $messageRequestTransfer = (new SearchFeedbackTicketMessageRequestTransfer())
            ->setIdSearchFeedbackTicket($idSearchFeedbackTicket)
            ->setBody('Thanks, looking into it.')
            ->setIdUser(1);

        // Act
        $ticketTransfer = $facade->replyToTicket($messageRequestTransfer);

        // Assert
        $messageTransfers = iterator_to_array($ticketTransfer->getMessages());
        $this->assertCount(2, $messageTransfers, 'The original submitted message plus the new reply.');
        $this->assertSame('Thanks, looking into it.', $messageTransfers[1]->getBody());
    }

    public function testChangeTicketStatusPersistsTheNewStatusAndReturnsTheUpdatedTicket(): void
    {
        // Arrange
        $facade = new SearchFeedbackFacade();
        $submitResponseTransfer = $facade->submitTicket($this->createRequestTransfer());
        $idSearchFeedbackTicket = $submitResponseTransfer->getTicketOrFail()->getIdSearchFeedbackTicketOrFail();

        // Act
        $ticketTransfer = $facade->changeTicketStatus($idSearchFeedbackTicket, SearchFeedbackConfig::STATUS_CLOSED);

        // Assert
        $this->assertSame(SearchFeedbackConfig::STATUS_CLOSED, $ticketTransfer->getStatus());
    }

    public function testGetTicketCollectionIncludesAFreshlySubmittedTicket(): void
    {
        // Arrange
        $facade = new SearchFeedbackFacade();
        $submitResponseTransfer = $facade->submitTicket($this->createRequestTransfer());
        $idSearchFeedbackTicket = $submitResponseTransfer->getTicketOrFail()->getIdSearchFeedbackTicketOrFail();

        // Act
        $collectionTransfer = $facade->getTicketCollection();
        $returnedIds = array_map(
            fn ($ticketTransfer) => $ticketTransfer->getIdSearchFeedbackTicket(),
            iterator_to_array($collectionTransfer->getTickets()),
        );

        // Assert
        $this->assertContains($idSearchFeedbackTicket, $returnedIds);
    }

    public function testFindTicketByIdReturnsNullForANonExistentId(): void
    {
        $this->assertNull((new SearchFeedbackFacade())->findTicketById(999999999));
    }

    protected function createRequestTransfer(): SearchFeedbackTicketRequestTransfer
    {
        return (new SearchFeedbackTicketRequestTransfer())
            ->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE)
            ->setSearchTerm('chair')
            ->setFilters([])
            ->setPageNumber(1)
            ->setSkuList([])
            ->setBody('The results feel off for this query.')
            ->setCustomerReference('CUST-FACADE-TEST')
            ->setStoreName('DE')
            ->setLocaleName('en_US');
    }
}
