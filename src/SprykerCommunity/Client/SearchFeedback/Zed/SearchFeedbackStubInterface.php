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

interface SearchFeedbackStubInterface
{
    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer
     */
    public function getTicketSrpSnapshot(SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer): SearchFeedbackTicketSrpSnapshotResponseTransfer;
}
