<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Plugin;

use Codeception\Test\Unit;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;

/**
 * Same rationale as the sibling {@see SubmitSearchFeedbackTicketPermissionPluginTest} — `getKey()` is
 * compared against `::KEY` directly by both the Yves event dispatcher plugin and the Zed gateway's
 * `CompanyUserPermissionAuthorizer`, so a drift here would silently break the replay permission gate.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group Plugin
 * @group ViewSearchFeedbackTicketReplayPermissionPluginTest
 * @group Portable
 */
class ViewSearchFeedbackTicketReplayPermissionPluginTest extends Unit
{
    public function testGetKeyReturnsTheClassKeyConstant(): void
    {
        $this->assertSame(
            ViewSearchFeedbackTicketReplayPermissionPlugin::KEY,
            (new ViewSearchFeedbackTicketReplayPermissionPlugin())->getKey(),
        );
    }

    public function testKeyMatchesTheClassNameByConvention(): void
    {
        // Spryker's permission-key convention is "the plugin class's own short name" — a project reading
        // `company_role_permission.csv` grants keyed by that string, so a drift here would silently break
        // every existing grant.
        $this->assertSame('ViewSearchFeedbackTicketReplayPermissionPlugin', ViewSearchFeedbackTicketReplayPermissionPlugin::KEY);
    }
}
