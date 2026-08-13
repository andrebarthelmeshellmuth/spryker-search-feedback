<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Persistence;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessageQuery;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManager;

/**
 * INTEGRATION TEST — real database, real rows, never mocked: every method here is a thin Propel
 * read-modify-write, so the one behavior actually worth protecting is that it persists and reads back
 * correctly (correct FK linkage, correct JSON encoding, safe no-op on a not-found id) — a mocked query
 * builder could confirm the right methods were called but never that.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Persistence
 * @group SearchFeedbackEntityManagerTest
 * @group NeedsDatabase
 */
class SearchFeedbackEntityManagerTest extends Unit
{
    public function testCreateTicketPersistsTheTicketAndItsFirstMessageInOneTransaction(): void
    {
        // Arrange
        $requestTransfer = $this->createRequestTransfer();

        // Act
        $ticketTransfer = (new SearchFeedbackEntityManager())->createTicket($requestTransfer);

        // Assert
        $this->assertNotNull($ticketTransfer->getIdSearchFeedbackTicket());
        $this->assertSame(SearchFeedbackConfig::TOPIC_RELEVANCE, $ticketTransfer->getTopic());
        $this->assertSame(SearchFeedbackConfig::STATUS_OPEN, $ticketTransfer->getStatus(), 'A new ticket must always start open.');
        $this->assertSame(['category' => ['123']], $ticketTransfer->getFilters());
        $this->assertSame(['SKU-1', 'SKU-2'], $ticketTransfer->getSkuList());

        $messageEntities = SpySearchFeedbackTicketMessageQuery::create()
            ->filterByFkSearchFeedbackTicket($ticketTransfer->getIdSearchFeedbackTicketOrFail())
            ->find();

        $this->assertCount(1, $messageEntities, 'createTicket() must persist exactly one message: the submitted body.');
        $this->assertSame('The results feel off for this query.', $messageEntities->getFirst()->getBody());
        $this->assertSame(SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER, $messageEntities->getFirst()->getAuthorType());
        $this->assertSame('CUST-ENTITY-MANAGER-1', $messageEntities->getFirst()->getCustomerReference());
    }

    public function testCreateTicketDefaultsPageNumberToOneWhenNotGiven(): void
    {
        // Arrange
        $requestTransfer = $this->createRequestTransfer()->setPageNumber(0);

        // Act
        $ticketTransfer = (new SearchFeedbackEntityManager())->createTicket($requestTransfer);

        // Assert
        $this->assertSame(1, $ticketTransfer->getPageNumber());
    }

    public function testAddMessageAppendsAFurtherMessageToAnExistingTicketsThread(): void
    {
        // Arrange
        $ticketTransfer = (new SearchFeedbackEntityManager())->createTicket($this->createRequestTransfer());

        // Act
        (new SearchFeedbackEntityManager())->addMessage(
            $ticketTransfer->getIdSearchFeedbackTicketOrFail(),
            'Thanks, looking into it.',
            SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER,
            1,
            null,
        );

        // Assert
        $messageEntities = SpySearchFeedbackTicketMessageQuery::create()
            ->filterByFkSearchFeedbackTicket($ticketTransfer->getIdSearchFeedbackTicketOrFail())
            ->orderByCreatedAt()
            ->find();

        $this->assertCount(2, $messageEntities, 'The original message plus the new reply.');
        $this->assertSame('Thanks, looking into it.', $messageEntities->getLast()->getBody());
        $this->assertSame(SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER, $messageEntities->getLast()->getAuthorType());
        $this->assertSame(1, $messageEntities->getLast()->getFkUser());
    }

    public function testChangeStatusUpdatesTheStatusOfAnExistingTicket(): void
    {
        // Arrange
        $ticketTransfer = (new SearchFeedbackEntityManager())->createTicket($this->createRequestTransfer());

        // Act
        (new SearchFeedbackEntityManager())->changeStatus($ticketTransfer->getIdSearchFeedbackTicketOrFail(), SearchFeedbackConfig::STATUS_ANSWERED);

        // Assert
        $this->assertSame(
            SearchFeedbackConfig::STATUS_ANSWERED,
            $this->findTicketEntity($ticketTransfer->getIdSearchFeedbackTicketOrFail())->getStatus(),
        );
    }

    public function testChangeStatusIsASafeNoOpForANonExistentId(): void
    {
        // Act & Assert (must not throw)
        (new SearchFeedbackEntityManager())->changeStatus(999999999, SearchFeedbackConfig::STATUS_CLOSED);
        $this->addToAssertionCount(1);
    }

    protected function createRequestTransfer(): SearchFeedbackTicketRequestTransfer
    {
        return (new SearchFeedbackTicketRequestTransfer())
            ->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE)
            ->setSearchTerm('chair')
            ->setFilters(['category' => ['123']])
            ->setPageNumber(2)
            ->setSkuList(['SKU-1', 'SKU-2'])
            ->setBody('The results feel off for this query.')
            ->setCustomerReference('CUST-ENTITY-MANAGER-1')
            ->setStoreName('DE')
            ->setLocaleName('en_US');
    }

    protected function findTicketEntity(int $idSearchFeedbackTicket): SpySearchFeedbackTicket
    {
        /** @var \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket $ticketEntity */
        $ticketEntity = SpySearchFeedbackTicketQuery::create()
            ->filterByIdSearchFeedbackTicket($idSearchFeedbackTicket)
            ->findOne();

        return $ticketEntity;
    }
}
