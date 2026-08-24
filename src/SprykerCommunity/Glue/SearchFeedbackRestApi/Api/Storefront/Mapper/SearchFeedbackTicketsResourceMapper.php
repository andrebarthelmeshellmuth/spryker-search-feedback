<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Mapper;

use Generated\Api\Storefront\SearchFeedbackTicketsStorefrontResource;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

class SearchFeedbackTicketsResourceMapper implements SearchFeedbackTicketsResourceMapperInterface
{
    public function mapResourceToTicketRequestTransfer(
        SearchFeedbackTicketsStorefrontResource $resource,
        SearchFeedbackTicketRequestTransfer $requestTransfer,
    ): SearchFeedbackTicketRequestTransfer {
        return $requestTransfer
            ->setTopic((string)$resource->getTopic())
            ->setBody((string)$resource->getBody())
            ->setSearchTerm((string)$resource->getSearchTerm())
            ->setFilters((array)$resource->getFilters())
            ->setPageNumber((int)$resource->getPageNumber())
            ->setSkuList(array_values(array_map(static fn (mixed $sku): string => (string)$sku, (array)$resource->getSkuList())));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function mapTicketTransferToResourceData(SearchFeedbackTicketTransfer $ticketTransfer, string $body): array
    {
        return [
            'id' => (string)$ticketTransfer->getIdSearchFeedbackTicket(),
            'topic' => $ticketTransfer->getTopic(),
            'body' => $body,
            'searchTerm' => $ticketTransfer->getSearchTerm(),
            'filters' => $ticketTransfer->getFilters(),
            'pageNumber' => $ticketTransfer->getPageNumber(),
            'skuList' => $ticketTransfer->getSkuList(),
            'status' => $ticketTransfer->getStatus(),
            'createdAt' => $ticketTransfer->getCreatedAt(),
        ];
    }
}
