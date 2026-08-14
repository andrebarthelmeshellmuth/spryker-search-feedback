<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback\Dependency\Plugin;

/**
 * The restore-side counterpart to {@see TermVectorSnapshotProviderPluginInterface} — same "the package
 * that WANTS the data owns the interface" direction as that one, but here it's search-feedback handing
 * data BACK, not asking for it: this package already has a ticket's frozen termvector snapshot in
 * storage, and {@see \SprykerCommunity\Client\SearchFeedback\Search\ReplayCapableSearch} calls every
 * registered implementation of this interface with it during a replay request, so a package like
 * search-ranking can restore its own request-scoped "last computed" state to the frozen value before its
 * search-debug overlay integration reads it.
 *
 * Confirmed live why this exists: query expansion (and whatever request-scoped "last computed" state it
 * sets, e.g. search-ranking's `SearchRankingClient::rememberLastSpecificityWeightingResult()`) still runs
 * fresh on every request, replay or not — only the actual Elasticsearch call itself gets replaced by
 * `ReplayCapableSearch`. Without this restore step, a replay's debug overlay silently shows whatever the
 * LIVE current settings compute, not the ones that actually scored the ticket at filing time — looking
 * identical to a correctly-frozen replay until someone changes a live setting and reopens the same replay.
 *
 * A shop with no implementation registered is unaffected — the frozen data is simply never restored
 * anywhere, same "additive, never a hard requirement" posture the whole frozen-replay feature has.
 */
interface TermVectorSnapshotRestorerPluginInterface
{
    /**
     * Specification:
     * - Restores whatever request-scoped state the implementing package needs so ITS OWN debug/overlay
     *   code reflects the frozen value, not a freshly (live) computed one, for the remainder of this
     *   request.
     * - Receives the exact string {@see TermVectorSnapshotProviderPluginInterface::getTermVectorSnapshot()}
     *   produced at capture time — this package never inspects or re-encodes it, so the implementing
     *   package owns decoding it back into whatever shape it needs.
     * - Must not perform any new Elasticsearch call.
     *
     * @api
     *
     * @param string $termVectorSnapshot
     */
    public function restoreTermVectorSnapshot(string $termVectorSnapshot): void;
}
