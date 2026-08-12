<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Persistence;

use Codeception\Test\Unit;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepository;

/**
 * INTEGRATION TEST — real database, real rows, never mocked: `getTicketCollection()`'s ordering and
 * `findTicketById()`'s "attach the real message thread / null when not found" behavior are exactly the
 * things a mocked query builder could never actually confirm.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Persistence
 * @group SearchFeedbackRepositoryTest
 */
class SearchFeedbackRepositoryTest extends Unit
{
    public function testGetTicketCollectionReturnsEveryTicketNewestFirst(): void
    {
        // Arrange
        $older = $this->createTestTicket('DE-TEST-REPO-OLDER');
        $older->setCreatedAt('2026-01-01 00:00:00');
        $older->save();

        $newer = $this->createTestTicket('DE-TEST-REPO-NEWER');
        $newer->setCreatedAt('2099-01-01 00:00:00');
        $newer->save();

        // Act
        $collectionTransfer = (new SearchFeedbackRepository())->getTicketCollection();
        $returnedIds = array_map(
            fn ($ticketTransfer) => $ticketTransfer->getIdSearchFeedbackTicket(),
            iterator_to_array($collectionTransfer->getTickets()),
        );

        // Assert — both present, newer strictly before older (this shared demo database may already hold
        // other real tickets, so this only asserts relative order between these two).
        $newerPosition = array_search($newer->getIdSearchFeedbackTicket(), $returnedIds, true);
        $olderPosition = array_search($older->getIdSearchFeedbackTicket(), $returnedIds, true);

        $this->assertNotFalse($newerPosition);
        $this->assertNotFalse($olderPosition);
        $this->assertLessThan($olderPosition, $newerPosition);
    }

    public function testFindTicketByIdReturnsTheTicketWithItsFullMessageThreadInOrder(): void
    {
        // Arrange
        $ticketEntity = $this->createTestTicket('DE-TEST-REPO-THREAD');

        $firstMessageEntity = new SpySearchFeedbackTicketMessage();
        $firstMessageEntity->setFkSearchFeedbackTicket($ticketEntity->getIdSearchFeedbackTicket());
        $firstMessageEntity->setBody('Original complaint.');
        $firstMessageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER);
        $firstMessageEntity->setCustomerReference('CUST-REPO-1');
        $firstMessageEntity->save();

        $secondMessageEntity = new SpySearchFeedbackTicketMessage();
        $secondMessageEntity->setFkSearchFeedbackTicket($ticketEntity->getIdSearchFeedbackTicket());
        $secondMessageEntity->setBody('Admin reply.');
        $secondMessageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER);
        $secondMessageEntity->save();

        // Act
        $ticketTransfer = (new SearchFeedbackRepository())->findTicketById($ticketEntity->getIdSearchFeedbackTicket());

        // Assert
        $this->assertNotNull($ticketTransfer);
        $messageTransfers = iterator_to_array($ticketTransfer->getMessages());
        $this->assertCount(2, $messageTransfers);
        $this->assertSame('Original complaint.', $messageTransfers[0]->getBody());
        $this->assertSame('Admin reply.', $messageTransfers[1]->getBody());
    }

    public function testFindTicketByIdReturnsNullForANonExistentId(): void
    {
        // Act
        $ticketTransfer = (new SearchFeedbackRepository())->findTicketById(999999999);

        // Assert
        $this->assertNull($ticketTransfer);
    }

    protected function createTestTicket(string $storeName): SpySearchFeedbackTicket
    {
        $ticketEntity = new SpySearchFeedbackTicket();
        $ticketEntity->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE);
        $ticketEntity->setSearchTerm('chair');
        $ticketEntity->setPageNumber(1);
        $ticketEntity->setStatus(SearchFeedbackConfig::STATUS_OPEN);
        $ticketEntity->setStoreName($storeName);
        $ticketEntity->setLocaleName('en_US');
        $ticketEntity->setCustomerReference('CUST-REPO-TEST');
        $ticketEntity->save();

        return $ticketEntity;
    }
}
