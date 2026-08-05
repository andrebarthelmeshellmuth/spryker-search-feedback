<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade;

use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

interface SearchFeedbackGuiToSearchFeedbackFacadeInterface
{
    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer;
}
