<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\Presentation;

use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PageObject\TicketListPage;
use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester;

/**
 * Checklist section 00 - PRE-FLIGHT: nav bundle and grid load without error (empty grid is fine on a
 * fresh install).
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group PreFlightCest
 * Add your own group annotations below this line
 */
class PreFlightCest
{
    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     *
     * @return void
     */
    public function _before(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amZed();
        $i->amLoggedInUser();
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     *
     * @return void
     */
    public function navBundleAndGridLoadWithoutError(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(TicketListPage::URL);
        $i->see('Search Feedback');
        $i->see('Tickets');
        $i->waitForElementVisible('.dt-container', 10);
        $i->see('ID');
        $i->see('Topic');
        $i->see('Search term');
        $i->see('Status');
        $i->see('Filed At');
        $i->see('Actions');
    }
}
