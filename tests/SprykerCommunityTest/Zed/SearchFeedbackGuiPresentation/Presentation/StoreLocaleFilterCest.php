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
 * The Store/Locale filter dropdowns' actual round trip: `IndexController::resolveStoreName()`/
 * `resolveLocaleName()` (query-param parsing) and `TicketTable::configure()`'s URL-param baking already
 * have direct unit coverage — this confirms the rendered `<select onchange="this.form.submit()">`
 * markup itself actually reloads the page with the right query string and reflects the selection back as
 * `selected`, which only a real browser can prove.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group StoreLocaleFilterCest
 */
class StoreLocaleFilterCest
{
    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     */
    public function _before(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amZed();
        $i->amLoggedInUser();
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     */
    public function selectingAStoreReloadsTheGridScopedToItAndKeepsTheSelectionShown(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible('#storeName', 10);

        $i->selectOption('#storeName', 'DE');

        $i->waitForElementVisible(TicketListPage::SELECTOR_TABLE, 10);
        $i->seeInCurrentUrl('storeName=DE');
        $i->seeOptionIsSelected('#storeName', 'DE');
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     */
    public function theDefaultAllStoresOptionAppliesNoFilter(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible('#storeName', 10);

        $i->dontSeeInCurrentUrl('storeName=');
        $i->seeOptionIsSelected('#storeName', 'All stores');
    }
}
