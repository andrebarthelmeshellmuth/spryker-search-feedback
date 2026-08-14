<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;

interface SearchFeedbackClientInterface
{
    /**
     * Specification:
     * - Sends a new ticket to Zed for persistence.
     * - The permission check is re-done server-side in Zed; a failing check comes back as
     *   isSuccess=false on the response, never an exception.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer;

    /**
     * Specification:
     * - Fetches a ticket's frozen SRP snapshot from Zed, for replay. isFound=false covers both "this
     *   ticket has no snapshot" and "not authorized" — the permission check is re-done server-side in Zed,
     *   same posture as submitTicket().
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer
     */
    public function getTicketSrpSnapshot(SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer): SearchFeedbackTicketSrpSnapshotResponseTransfer;

    /**
     * Specification:
     * - Reads and clears (one-time use) the SRP snapshot captured under the given token during the search
     *   that rendered the ticket form — see `SearchFeedbackSnapshotContext`. Returns null if the token is
     *   missing/unknown/already consumed/expired — never an error, since a submitter posting without ever
     *   having loaded a capturing search page (or a shop that hasn't wired the capture plugin at all) is an
     *   expected, not exceptional, case.
     *
     * @api
     *
     * @param string $token
     */
    public function consumeSnapshot(string $token): ?SearchFeedbackTicketSrpSnapshotTransfer;

    /**
     * Specification:
     * - Whether at least one TermVectorSnapshotProviderPluginInterface implementation is registered via
     *   SearchFeedbackDependencyProvider::getTermVectorSnapshotProviderPlugins() — an optional integration
     *   point, most commonly wired to search-ranking's own SearchFeedbackTermVectorSnapshotProviderPlugin
     *   (see README step 11). Exists so a caller outside this module (the Yves check-installation page)
     *   can verify the registration without reaching into the Factory directly.
     *
     * @api
     */
    public function hasTermVectorSnapshotProviderPlugin(): bool;

    /**
     * Specification:
     * - Calls every registered `TermVectorSnapshotRestorerPluginInterface` implementation with the given
     *   snapshot string, so each can restore its own request-scoped "last computed" state to the frozen
     *   value — see that interface for why. Called by `ReplayCapableSearch` during a replay request; not
     *   meant to be called by application code directly.
     * - A shop with no restorer plugin registered is a safe no-op.
     *
     * @api
     *
     * @param string $termVectorSnapshot
     */
    public function restoreTermVectorSnapshot(string $termVectorSnapshot): void;
}
