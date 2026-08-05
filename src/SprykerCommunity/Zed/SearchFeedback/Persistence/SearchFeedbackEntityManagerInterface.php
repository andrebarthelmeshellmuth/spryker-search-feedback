<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

interface SearchFeedbackEntityManagerInterface
{
    /**
     * Creates the ticket row and its first message (the customer's submitted body) in one transaction.
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function createTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $body
     * @param string $authorType
     * @param int|null $idUser
     * @param string|null $customerReference
     */
    public function addMessage(
        int $idSearchFeedbackTicket,
        string $body,
        string $authorType,
        ?int $idUser,
        ?string $customerReference,
    ): void;

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeStatus(int $idSearchFeedbackTicket, string $status): void;
}
