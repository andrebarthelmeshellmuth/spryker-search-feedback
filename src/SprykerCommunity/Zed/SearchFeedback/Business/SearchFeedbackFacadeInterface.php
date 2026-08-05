<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Business;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

interface SearchFeedbackFacadeInterface
{
    /**
     * Specification:
     * - Creates a new ticket (and its first message, the submitted body) from a Yves-originated request.
     * - Does NOT re-check the submitter's permission — that's done by the caller
     *   ({@see \SprykerCommunity\Zed\SearchFeedback\Communication\Controller\GatewayController}), the only
     *   entry point this method is meant to be reached through.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer;

    /**
     * Specification:
     * - Appends a Zed-admin reply to an existing ticket's thread.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer;

    /**
     * Specification:
     * - Changes a ticket's status (open/answered/closed). Zed ACL, not this method, is what should keep
     *   this restricted to a "ticket worker" group in a real install — see the package README.
     *
     * @api
     *
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer;

    /**
     * Specification:
     * - Returns every ticket, newest first. No row-level scoping by submitter.
     *
     * @api
     */
    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer;

    /**
     * Specification:
     * - Returns one ticket with its full message thread, or null if it doesn't exist.
     *
     * @api
     *
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer;
}
