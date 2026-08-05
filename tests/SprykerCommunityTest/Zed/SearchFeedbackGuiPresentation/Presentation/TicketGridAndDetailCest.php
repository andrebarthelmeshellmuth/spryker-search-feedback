<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\Presentation;

use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PageObject\TicketDetailPage;
use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PageObject\TicketListPage;
use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester;

/**
 * Checklist section 01 - TICKET GRID + DETAIL: reading an existing ticket in Zed. Needs at least one
 * ticket to exist (created by the sibling Yves suite's submission test, or by any real prior usage of
 * this demoshop) - gracefully comments and skips if the grid is genuinely empty.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group TicketGridAndDetailCest
 * Add your own group annotations below this line
 */
class TicketGridAndDetailCest
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
    public function detailPageShowsFullTicketContextAndConversation(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible(TicketListPage::SELECTOR_TABLE, 10);

        if (!$i->tryToSeeElement(TicketListPage::SELECTOR_VIEW_BUTTON)) {
            $i->comment('No ticket exists yet; skipping. Run the sibling Yves suite\'s TicketSubmissionCest first for full coverage.');

            return;
        }

        $viewHref = $i->grabAttributeFrom(TicketListPage::SELECTOR_VIEW_BUTTON, 'href');
        $i->amOnPage($viewHref);

        $i->see('Ticket Context');
        $i->see('Topic');
        $i->see('Search term');
        $i->see('Filters');
        $i->see('Page');
        $i->see('SKUs shown');
        $i->see('Store / Locale');
        $i->see('Status');
        $i->see('Filed At');
        $i->see('Conversation');

        // At least one "Mark ..." link for a status the ticket ISN'T currently in.
        $i->seeElement("//a[contains(@href, '/search-feedback-gui/detail/change-status') and contains(., 'Mark')]");

        // The reply form is always present (any ticket worker can post a reply regardless of status).
        $i->seeElement('#' . TicketDetailPage::FIELD_REPLY_BODY);
        $i->see(TicketDetailPage::POST_REPLY_BUTTON_TEXT);
    }
}
