<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchFeedback\Plugin;

use Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface;

/**
 * Grants the ability to view a ticket's frozen search-results-page replay in Yves — the "View SRP" link
 * on the Zed ticket detail page. A separate, orthogonal permission from
 * {@see \SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin}: filing
 * a ticket and reviewing one back are different asks, granted independently, same as the sibling
 * search-ranking-optimizer package keeps rating and reviewing separate.
 *
 * Whoever reviews a replay needs an actual Yves company-user login with this permission granted — there is
 * no separate "Zed admin" credential system this plugin can check instead. An unauthenticated or
 * unauthorized visit to a replay link is expected to fail Symfony's standard access-denied handling and
 * bounce through login, landing back on the replay page afterward (`target_path`) — no custom redirect
 * plumbing needed. See `SearchFeedbackReplayContextEventDispatcherPlugin` for where this is checked.
 *
 * For Zed & Client PermissionDependencyProvider::getPermissionPlugins() registration.
 */
class ViewSearchFeedbackTicketReplayPermissionPlugin implements PermissionPluginInterface
{
    /**
     * @var string
     */
    public const KEY = 'ViewSearchFeedbackTicketReplayPermissionPlugin';

    public function getKey(): string
    {
        return static::KEY;
    }
}
