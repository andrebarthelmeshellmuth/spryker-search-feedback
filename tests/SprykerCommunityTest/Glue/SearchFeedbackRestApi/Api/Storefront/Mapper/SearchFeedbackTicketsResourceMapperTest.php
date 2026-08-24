<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Glue\SearchFeedbackRestApi\Api\Storefront\Mapper;

use Codeception\Test\Unit;
use Generated\Api\Storefront\SearchFeedbackTicketsStorefrontResource;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Mapper\SearchFeedbackTicketsResourceMapper;

/**
 * @group SprykerCommunityTest
 * @group Glue
 * @group SearchFeedbackRestApi
 * @group SearchFeedbackTicketsResourceMapperTest
 */
class SearchFeedbackTicketsResourceMapperTest extends Unit
{
    public function testMapResourceToTicketRequestTransferCopiesWritableAttributesOnly(): void
    {
        // Arrange
        $resource = new SearchFeedbackTicketsStorefrontResource();
        $resource->setTopic('Irrelevant results');
        $resource->setBody('The first three results are wrong.');
        $resource->setSearchTerm('garden chair');
        $resource->setFilters(['category' => ['123']]);
        $resource->setPageNumber(2);
        $resource->setSkuList(['001_1', '001_2']);

        $mapper = new SearchFeedbackTicketsResourceMapper();

        // Act
        $requestTransfer = $mapper->mapResourceToTicketRequestTransfer($resource, new SearchFeedbackTicketRequestTransfer());

        // Assert
        $this->assertSame('Irrelevant results', $requestTransfer->getTopic());
        $this->assertSame('The first three results are wrong.', $requestTransfer->getBody());
        $this->assertSame('garden chair', $requestTransfer->getSearchTerm());
        $this->assertSame(['category' => ['123']], $requestTransfer->getFilters());
        $this->assertSame(2, $requestTransfer->getPageNumber());
        $this->assertSame(['001_1', '001_2'], $requestTransfer->getSkuList());
        $this->assertNull($requestTransfer->getCustomerReference());
        $this->assertNull($requestTransfer->getStoreName());
        $this->assertNull($requestTransfer->getLocaleName());
    }

    public function testMapTicketTransferToResourceDataUsesGivenBodyNotAMessageField(): void
    {
        // Arrange
        $ticketTransfer = (new SearchFeedbackTicketTransfer())
            ->setIdSearchFeedbackTicket(42)
            ->setTopic('Irrelevant results')
            ->setSearchTerm('garden chair')
            ->setFilters(['category' => ['123']])
            ->setPageNumber(1)
            ->setSkuList(['001_1'])
            ->setStatus('open')
            ->setCreatedAt('2026-08-24T10:00:00+00:00');

        $mapper = new SearchFeedbackTicketsResourceMapper();

        // Act
        $resourceData = $mapper->mapTicketTransferToResourceData($ticketTransfer, 'The submitted message body.');

        // Assert
        $this->assertSame([
            'id' => '42',
            'topic' => 'Irrelevant results',
            'body' => 'The submitted message body.',
            'searchTerm' => 'garden chair',
            'filters' => ['category' => ['123']],
            'pageNumber' => 1,
            'skuList' => ['001_1'],
            'status' => 'open',
            'createdAt' => '2026-08-24T10:00:00+00:00',
        ], $resourceData);
    }
}
