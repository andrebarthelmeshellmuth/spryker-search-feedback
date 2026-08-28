<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Fixture;

use SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotRestorerPluginInterface;

/**
 * The restore-side counterpart to {@see FakeTermVectorSnapshotProviderPlugin}, standing in for
 * search-ranking's own registered implementation. Records every snapshot string handed to it during a
 * replay so a test can assert it received exactly what the provider produced at capture time, with no
 * `spryker-community/search-ranking` test dependency.
 */
class FakeTermVectorSnapshotRestorerPlugin implements TermVectorSnapshotRestorerPluginInterface
{
    /**
     * @var array<string>
     */
    public array $restoredSnapshots = [];

    public function restoreTermVectorSnapshot(string $termVectorSnapshot): void
    {
        $this->restoredSnapshots[] = $termVectorSnapshot;
    }
}
