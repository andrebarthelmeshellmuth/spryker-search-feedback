<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Fixture;

use SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface;

/**
 * A minimal in-repo implementation of the provider seam, standing in for search-ranking's own
 * `SearchFeedbackTermVectorSnapshotProviderPlugin` so the capture path can be exercised against a real
 * implementation without a `spryker-community/search-ranking` test dependency. Returns whatever string
 * (or null) it was constructed with, and never performs any Elasticsearch call — the two things the
 * interface contract actually requires.
 */
class FakeTermVectorSnapshotProviderPlugin implements TermVectorSnapshotProviderPluginInterface
{
    public function __construct(protected ?string $termVectorSnapshot)
    {
    }

    public function getTermVectorSnapshot(): ?string
    {
        return $this->termVectorSnapshot;
    }
}
