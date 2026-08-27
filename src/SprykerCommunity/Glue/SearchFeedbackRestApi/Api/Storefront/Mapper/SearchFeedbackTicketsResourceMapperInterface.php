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

interface SearchFeedbackTicketsResourceMapperInterface
{
    /**
     * Copies the writable attributes of a `SearchFeedbackTicketsStorefrontResource` onto a new
     * `SearchFeedbackTicketRequestTransfer`. Does not set `customerReference`/`storeName`/`localeName` —
     * those are resolved server-side by the Processor from the authenticated session/store context, never
     * trusted from client input.
     */
    public function mapResourceToTicketRequestTransfer(
        SearchFeedbackTicketsStorefrontResource $resource,
        SearchFeedbackTicketRequestTransfer $requestTransfer,
    ): SearchFeedbackTicketRequestTransfer;

    /**
     * Builds the resource-shaped array payload used to denormalize a `SearchFeedbackTicketsStorefrontResource`
     * from the ticket returned by `SearchFeedbackClient::submitTicket()`. `body` is echoed back from the
     * submitted request rather than re-read from `$ticketTransfer->getMessages()` — the ticket transfer
     * has no top-level `body` field, the customer's original message text instead lives at
     * `messages[0].body` in the persisted thread.
     *
     * @return array<string, mixed>
     */
    public function mapTicketTransferToResourceData(SearchFeedbackTicketTransfer $ticketTransfer, string $body): array;
}
