<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Persistence\Propel\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Persistence\Propel\Mapper\SearchFeedbackMapper;

/**
 * Pure unit tests — no database. Covers the two things this class computes rather than just copies:
 * `filters`/`skuList` JSON round-tripping (including the null/empty edge cases the entity column allows),
 * and the author-label branch (customer reference vs. Zed username).
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Persistence
 * @group SearchFeedbackMapperTest
 */
class SearchFeedbackMapperTest extends Unit
{
    public function testMapTicketEntityToTransferDecodesJsonFiltersAndSkuList(): void
    {
        // Arrange
        $ticketEntity = new SpySearchFeedbackTicket();
        $ticketEntity->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE);
        $ticketEntity->setSearchTerm('chair');
        $ticketEntity->setFilters(json_encode(['category' => ['123']]));
        $ticketEntity->setPageNumber(1);
        $ticketEntity->setSkuList(json_encode(['SKU-1', 'SKU-2']));
        $ticketEntity->setStatus(SearchFeedbackConfig::STATUS_OPEN);
        $ticketEntity->setStoreName('DE');
        $ticketEntity->setLocaleName('en_US');
        $ticketEntity->setCustomerReference('CUST-MAPPER-1');

        // Act
        $ticketTransfer = (new SearchFeedbackMapper())->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer());

        // Assert
        $this->assertSame(['category' => ['123']], $ticketTransfer->getFilters());
        $this->assertSame(['SKU-1', 'SKU-2'], $ticketTransfer->getSkuList());
    }

    public function testMapTicketEntityToTransferTreatsNullFiltersAndSkuListAsEmptyArrays(): void
    {
        // Arrange — the schema declares both columns nullable (a ticket can be filed with no active
        // facets and no SKUs rendered yet).
        $ticketEntity = new SpySearchFeedbackTicket();
        $ticketEntity->setTopic(SearchFeedbackConfig::TOPIC_OTHER);
        $ticketEntity->setSearchTerm('');
        $ticketEntity->setFilters(null);
        $ticketEntity->setPageNumber(1);
        $ticketEntity->setSkuList(null);
        $ticketEntity->setStatus(SearchFeedbackConfig::STATUS_OPEN);
        $ticketEntity->setStoreName('DE');
        $ticketEntity->setLocaleName('en_US');
        $ticketEntity->setCustomerReference('CUST-MAPPER-2');

        // Act
        $ticketTransfer = (new SearchFeedbackMapper())->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer());

        // Assert
        $this->assertSame([], $ticketTransfer->getFilters());
        $this->assertSame([], $ticketTransfer->getSkuList());
    }

    public function testMapTicketEntityToTransferAddsOneMappedMessagePerGivenMessageEntity(): void
    {
        // Arrange
        $ticketEntity = $this->createMinimalTicketEntity();
        $messageEntity = new SpySearchFeedbackTicketMessage();
        $messageEntity->setBody('Hello');
        $messageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER);
        $messageEntity->setCustomerReference('CUST-MAPPER-3');

        // Act
        $ticketTransfer = (new SearchFeedbackMapper())->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer(), [$messageEntity]);

        // Assert
        $messageTransfers = iterator_to_array($ticketTransfer->getMessages());
        $this->assertCount(1, $messageTransfers);
        $this->assertSame('Hello', $messageTransfers[0]->getBody());
    }

    public function testMapMessageEntityToTransferUsesTheCustomerReferenceAsTheAuthorLabelForACustomerMessage(): void
    {
        // Arrange
        $messageEntity = new SpySearchFeedbackTicketMessage();
        $messageEntity->setBody('A customer message.');
        $messageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER);
        $messageEntity->setCustomerReference('CUST-MAPPER-4');

        // Act
        $messageTransfer = (new SearchFeedbackMapper())->mapMessageEntityToTransfer($messageEntity, new SearchFeedbackTicketMessageTransfer());

        // Assert
        $this->assertSame('CUST-MAPPER-4', $messageTransfer->getAuthorLabel());
    }

    public function testMapMessageEntityToTransferUsesAnEmptyLabelForAZedUserMessageWithNoLinkedUser(): void
    {
        // Arrange — a real Zed user relation needs a DB row to resolve; unlinked here on purpose to prove
        // the fallback (`?? ''`) rather than a real username, which the DB-backed EntityManager/Repository
        // tests already cover end-to-end.
        $messageEntity = new SpySearchFeedbackTicketMessage();
        $messageEntity->setBody('A reply with no resolvable user.');
        $messageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER);

        // Act
        $messageTransfer = (new SearchFeedbackMapper())->mapMessageEntityToTransfer($messageEntity, new SearchFeedbackTicketMessageTransfer());

        // Assert
        $this->assertSame('', $messageTransfer->getAuthorLabel());
    }

    protected function createMinimalTicketEntity(): SpySearchFeedbackTicket
    {
        $ticketEntity = new SpySearchFeedbackTicket();
        $ticketEntity->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE);
        $ticketEntity->setSearchTerm('chair');
        $ticketEntity->setPageNumber(1);
        $ticketEntity->setStatus(SearchFeedbackConfig::STATUS_OPEN);
        $ticketEntity->setStoreName('DE');
        $ticketEntity->setLocaleName('en_US');
        $ticketEntity->setCustomerReference('CUST-MAPPER-MINIMAL');

        return $ticketEntity;
    }
}
