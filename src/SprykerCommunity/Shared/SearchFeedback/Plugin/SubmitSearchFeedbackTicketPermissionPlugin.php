<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchFeedback\Plugin;

use Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface;

/**
 * Grants the **Feedback Admin** role: the free-text ticket form on the storefront search results page,
 * submitting a ticket about the currently visible set of results. A separate, orthogonal permission from
 * search-ranking-optimizer's `RateSearchRelevancePermissionPlugin` — rating individual products and
 * filing a qualitative complaint about a results page are different asks, granted independently.
 *
 * For Zed & Client PermissionDependencyProvider::getPermissionPlugins() registration.
 */
class SubmitSearchFeedbackTicketPermissionPlugin implements PermissionPluginInterface
{
    /**
     * @var string
     */
    public const KEY = 'SubmitSearchFeedbackTicketPermissionPlugin';

    public function getKey(): string
    {
        return static::KEY;
    }
}
