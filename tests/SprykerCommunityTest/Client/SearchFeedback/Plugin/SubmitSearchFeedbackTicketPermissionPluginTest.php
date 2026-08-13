<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Plugin;

use Codeception\Test\Unit;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;

/**
 * The Client- and Zed-side permission checks compare against `::KEY` directly (see
 * `CompanyUserPermissionAuthorizer::isAuthorized()`'s caller in `GatewayController`), so `getKey()` must
 * keep returning exactly that constant, not a derived or reformatted string.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group Plugin
 * @group SubmitSearchFeedbackTicketPermissionPluginTest
 * @group Portable
 */
class SubmitSearchFeedbackTicketPermissionPluginTest extends Unit
{
    public function testGetKeyReturnsTheClassKeyConstant(): void
    {
        $this->assertSame(
            SubmitSearchFeedbackTicketPermissionPlugin::KEY,
            (new SubmitSearchFeedbackTicketPermissionPlugin())->getKey(),
        );
    }

    public function testKeyMatchesTheClassNameByConvention(): void
    {
        // Spryker's permission-key convention is "the plugin class's own short name" — a project reading
        // `company_role_permission.csv` grants keyed by that string, so a drift here would silently break
        // every existing grant.
        $this->assertSame('SubmitSearchFeedbackTicketPermissionPlugin', SubmitSearchFeedbackTicketPermissionPlugin::KEY);
    }
}
