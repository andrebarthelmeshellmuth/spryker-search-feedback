<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback\Search;

use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToSessionClientInterface;

/**
 * Bridges a captured SRP snapshot from the GET request that ran the live search (where
 * `SearchFeedbackSnapshotResultFormatterPlugin` runs) to the LATER, separate POST request that submits the
 * ticket (`SubmitTicketController`) — these are two different HTTP requests/PHP processes, so a plain
 * request-scoped in-memory object cannot bridge them; this wraps session storage instead, keyed by a
 * short-lived, random per-search token embedded in the ticket form as a hidden field (see
 * `SearchFeedbackConfig::KEY_SNAPSHOT_TOKEN`). Only the TOKEN is client-visible — the captured
 * response/query/termvector data itself is never sent to the browser, so it cannot be forged, matching the
 * trust-boundary note on `SearchFeedbackTicketSrpSnapshot` in the transfer.xml.
 *
 * One-time use by design: `consume()` reads AND removes the session entry, so a stale token from an old
 * page view can't accidentally get reattached to an unrelated later ticket, and the session doesn't
 * accumulate an entry per search a customer ever ran.
 */
class SearchFeedbackSnapshotContext
{
    /**
     * @var string
     */
    protected const SESSION_KEY_PREFIX = 'search_feedback_srp_snapshot.';

    /**
     * A session accumulating one entry per uncaptured search (a customer who searches but never opens the
     * ticket form) would grow unboundedly across a long-lived session — capped by evicting the oldest
     * entries once this many are pending.
     *
     * @var int
     */
    protected const MAX_PENDING_SNAPSHOTS = 5;

    /**
     * @var string
     */
    protected const SESSION_KEY_PENDING_TOKENS = 'search_feedback_srp_snapshot_pending_tokens';

    /**
     * @param \SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToSessionClientInterface $sessionClient
     */
    public function __construct(protected SearchFeedbackToSessionClientInterface $sessionClient)
    {
    }

    /**
     * @param string $rawResponse
     * @param string $queryDsl
     * @param string|null $requestParameters
     * @param bool $hasTermVectorSnapshot
     * @param string|null $termVectorSnapshot
     *
     * @return string The token to embed in the ticket form.
     */
    public function capture(
        string $rawResponse,
        string $queryDsl,
        ?string $requestParameters,
        bool $hasTermVectorSnapshot,
        ?string $termVectorSnapshot,
    ): string {
        $token = bin2hex(random_bytes(16));

        $this->sessionClient->set(static::SESSION_KEY_PREFIX . $token, [
            'rawResponse' => $rawResponse,
            'queryDsl' => $queryDsl,
            'requestParameters' => $requestParameters,
            'hasTermVectorSnapshot' => $hasTermVectorSnapshot,
            'termVectorSnapshot' => $termVectorSnapshot,
        ]);

        $this->trackPendingToken($token);

        return $token;
    }

    /**
     * @param string $token
     *
     * @return array{rawResponse: string, queryDsl: string, requestParameters: string|null, hasTermVectorSnapshot: bool, termVectorSnapshot: string|null}|null
     */
    public function consume(string $token): ?array
    {
        $sessionKey = static::SESSION_KEY_PREFIX . $token;

        /** @var array{rawResponse: string, queryDsl: string, requestParameters: string|null, hasTermVectorSnapshot: bool, termVectorSnapshot: string|null}|null $snapshot */
        $snapshot = $this->sessionClient->get($sessionKey);
        $this->sessionClient->remove($sessionKey);

        return $snapshot;
    }

    /**
     * @param string $token
     */
    protected function trackPendingToken(string $token): void
    {
        /** @var array<string> $pendingTokens */
        $pendingTokens = $this->sessionClient->get(static::SESSION_KEY_PENDING_TOKENS, []);
        $pendingTokens[] = $token;

        while (count($pendingTokens) > static::MAX_PENDING_SNAPSHOTS) {
            $evictedToken = array_shift($pendingTokens);
            $this->sessionClient->remove(static::SESSION_KEY_PREFIX . $evictedToken);
        }

        $this->sessionClient->set(static::SESSION_KEY_PENDING_TOKENS, $pendingTokens);
    }
}
