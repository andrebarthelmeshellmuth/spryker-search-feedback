<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback\Zed;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;

class SearchFeedbackStub implements SearchFeedbackStubInterface
{
    /**
     * @param \SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface $zedRequestClient
     */
    public function __construct(protected SearchFeedbackToZedRequestInterface $zedRequestClient)
    {
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/search-feedback/gateway/submit-ticket',
            $requestTransfer,
        );

        return $responseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer
     */
    public function getTicketSrpSnapshot(SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer): SearchFeedbackTicketSrpSnapshotResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/search-feedback/gateway/get-ticket-srp-snapshot',
            $requestTransfer,
        );

        return $responseTransfer;
    }
}
