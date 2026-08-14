<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

interface SearchFeedbackRepositoryInterface
{
    /**
     * Every ticket, newest first — no row-level scoping by submitter (see package README: Zed users and
     * Yves customers are separate identity systems with no built-in link, so every Zed admin with access
     * to this module sees the same full list).
     */
    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer;

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findSrpSnapshotByTicketId(int $idSearchFeedbackTicket): ?SearchFeedbackTicketSrpSnapshotTransfer;
}
