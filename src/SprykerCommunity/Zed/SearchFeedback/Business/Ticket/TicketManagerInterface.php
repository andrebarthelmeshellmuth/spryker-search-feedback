<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Business\Ticket;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

interface TicketManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer;

    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer;
}
