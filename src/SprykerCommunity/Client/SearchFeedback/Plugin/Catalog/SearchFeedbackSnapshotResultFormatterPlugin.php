<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback\Plugin\Catalog;

use Elastica\ResultSet;
use Spryker\Client\SearchElasticsearch\Plugin\ResultFormatter\AbstractElasticsearchResultFormatterPlugin;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * @method \SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory getFactory()
 */
class SearchFeedbackSnapshotResultFormatterPlugin extends AbstractElasticsearchResultFormatterPlugin
{
    /**
     * @var string
     */
    public const NAME = SearchFeedbackConfig::SEARCH_RESULT_KEY;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     * - Captures the raw Elasticsearch response and the fully-expanded query DSL for the current search
     *   into session storage (see `SearchFeedbackSnapshotContext`), and returns a one-time lookup token for
     *   the ticket form to carry as a hidden field — never the captured data itself.
     * - Also captures termvector data via any registered
     *   `TermVectorSnapshotProviderPluginInterface` implementation, if one is wired (optional integration,
     *   see `SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS`).
     * - Runs unconditionally for every catalog search — unlike search-debug's own result formatter, there
     *   is no permission gate here: capturing is passive and server-side-only, nothing is exposed to the
     *   customer beyond an opaque token, so there is nothing that needs gating until the ticket-submit
     *   permission check itself (SubmitSearchFeedbackTicketPermissionPlugin, checked in the Yves
     *   controller, same as today).
     * - Skips capturing entirely when this request is itself a frozen-replay view
     *   ({@see \SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET}
     *   present in `$requestParameters`, same signal {@see \SprykerCommunity\Client\SearchFeedback\Search\ReplayCapableSearch}
     *   itself keys off). Two independent reasons: there is nothing new worth freezing while already looking
     *   at frozen data, and the `$searchResult` in that case wraps a `Query` object
     *   `ReplayCapableSearch::buildReplayResultSet()` rebuilt via `Query::create($rawArrayFromStorage)` —
     *   a plain-array reconstruction, not the fluent builder API a live query goes through. Confirmed live:
     *   Elastica's own `Query::toArray()` throws `Undefined array key "suggest"` on that reconstructed
     *   object whenever the frozen query DSL includes a suggest block, because `toArray()` expects the
     *   double-nested shape `setSuggest()` produces and the reconstruction never re-nests it.
     *
     * @param \Elastica\ResultSet $searchResult
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string, mixed>
     */
    protected function formatSearchResult(ResultSet $searchResult, array $requestParameters): array
    {
        if (isset($requestParameters[SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET])) {
            return [
                SearchFeedbackConfig::KEY_SNAPSHOT_TOKEN => null,
            ];
        }

        $rawResponse = json_encode($searchResult->getResponse()->getData()) ?: '{}';
        $queryDsl = json_encode($searchResult->getQuery()->toArray()) ?: '{}';
        $encodedRequestParameters = $requestParameters === [] ? null : (json_encode($requestParameters) ?: null);

        $termVectorSnapshot = $this->getTermVectorSnapshot();

        $token = $this->getFactory()->getSnapshotContext()->capture(
            $rawResponse,
            $queryDsl,
            $encodedRequestParameters,
            $termVectorSnapshot !== null,
            $termVectorSnapshot,
        );

        return [
            SearchFeedbackConfig::KEY_SNAPSHOT_TOKEN => $token,
        ];
    }

    /**
     * First plugin to return non-null wins — in practice at most one is ever registered by a project, this
     * just avoids a hard assumption on exactly one.
     */
    protected function getTermVectorSnapshot(): ?string
    {
        foreach ($this->getFactory()->getTermVectorSnapshotProviderPlugins() as $termVectorSnapshotProviderPlugin) {
            $termVectorSnapshot = $termVectorSnapshotProviderPlugin->getTermVectorSnapshot();

            if ($termVectorSnapshot !== null) {
                return $termVectorSnapshot;
            }
        }

        return null;
    }
}
