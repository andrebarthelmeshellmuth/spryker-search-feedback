<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback\Dependency\Plugin;

/**
 * Optional integration point owned by search-feedback (the host that wants this data), implemented by a
 * sibling package that actually computes it — today that's search-ranking's
 * `SearchFeedbackTermVectorSnapshotProviderPlugin`, which JSON-encodes the
 * `SearchRankingSpecificityWeightingResultTransfer` search-ranking's own specificity weighting ALREADY
 * computed this request for its own scoring (via its existing `getLastSpecificityWeightingResult()` hook)
 * — NOT a raw `_termvectors` response, which search-ranking never retains past the request that fetched it.
 * This mirrors the direction search-debug's own `ProductDebugDataExpanderPluginInterface` already
 * establishes: the package that WANTS the data owns the interface, the package that HAS the data implements
 * it, and a project only gets the integration if it explicitly registers the plugin — see
 * `SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS`.
 *
 * A shop with no implementation registered (or where the registered implementation computed nothing this
 * request, e.g. `isSpecificityWeightingEnabled()` was off) sees a snapshot with no termvector data —
 * `SearchFeedbackSnapshotResultFormatterPlugin` treats that identically to "not installed", never an error.
 */
interface TermVectorSnapshotProviderPluginInterface
{
    /**
     * Specification:
     * - Returns a JSON-encoded summary of whatever specificity/relevance-weighting computation the
     *   implementing package already did for the current request's search, or null if it computed nothing
     *   this request (e.g. specificity weighting is disabled, or the search had no search string).
     * - Must not perform any new Elasticsearch call — only expose data already computed for another
     *   purpose this same request.
     *
     * @api
     */
    public function getTermVectorSnapshot(): ?string;
}
