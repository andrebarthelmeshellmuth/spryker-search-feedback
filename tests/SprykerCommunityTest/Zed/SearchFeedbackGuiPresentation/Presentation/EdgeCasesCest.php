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
 * Checklist section 05 - EDGE CASES FIXED THIS PASS: crafted URLs should fail gracefully, not crash.
 * Uses a deliberately bogus ticket id (999999999) — always runs regardless of what data exists, unlike
 * most of this suite's other Cests.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group EdgeCasesCest
 * Add your own group annotations below this line
 */
class EdgeCasesCest
{
    /**
     * @var int
     */
    protected const BOGUS_TICKET_ID = 999999999;

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
    public function unknownStatusValueRedirectsBackToTheTicketWithAFriendlyMessage(SearchFeedbackGuiPresentationTester $i): void
    {
        // Needs a REAL existing ticket id, not the bogus one used by the other two tests below - a
        // nonexistent id here would chain into the "ticket does not exist" redirect too (changeStatusAction
        // redirects to the detail page, which then itself redirects again to the list since the ticket
        // can't be found), landing on the list instead of the detail page this test is meant to verify.
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible(TicketListPage::SELECTOR_TABLE, 10);

        if (!$i->tryToSeeElement(TicketListPage::SELECTOR_VIEW_BUTTON)) {
            $i->comment('No ticket exists yet; skipping. Run the sibling Yves suite\'s TicketSubmissionCest first for full coverage.');

            return;
        }

        $viewHref = $i->grabAttributeFrom(TicketListPage::SELECTOR_VIEW_BUTTON, 'href');
        preg_match('/' . preg_quote(TicketDetailPage::URL_PARAM_ID, '/') . '=(\d+)/', $viewHref, $matches);
        $idSearchFeedbackTicket = (int)$matches[1];

        $i->amOnPage(sprintf(
            '%s?%s=%d&status=bogus',
            TicketDetailPage::URL_CHANGE_STATUS,
            TicketDetailPage::URL_PARAM_ID,
            $idSearchFeedbackTicket,
        ));

        $i->see(TicketDetailPage::FLASH_MESSAGE_UNKNOWN_STATUS);
        $i->seeInCurrentUrl(TicketDetailPage::URL);
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     *
     * @return void
     */
    public function nonexistentTicketIdOnChangeStatusRedirectsToTheListNotACrash(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(sprintf(
            '%s?%s=%d&status=closed',
            TicketDetailPage::URL_CHANGE_STATUS,
            TicketDetailPage::URL_PARAM_ID,
            static::BOGUS_TICKET_ID,
        ));

        $i->see(sprintf(TicketDetailPage::FLASH_MESSAGE_TICKET_NOT_FOUND_FORMAT, static::BOGUS_TICKET_ID));
        $i->seeInCurrentUrl(TicketListPage::URL);
        $i->dontSeeInCurrentUrl(TicketDetailPage::URL_PARAM_ID);
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     *
     * @return void
     */
    public function nonexistentTicketIdOnDetailPageRedirectsToTheListNotACrash(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(sprintf(
            '%s?%s=%d',
            TicketDetailPage::URL,
            TicketDetailPage::URL_PARAM_ID,
            static::BOGUS_TICKET_ID,
        ));

        $i->see(sprintf(TicketDetailPage::FLASH_MESSAGE_TICKET_NOT_FOUND_FORMAT, static::BOGUS_TICKET_ID));
        $i->seeInCurrentUrl(TicketListPage::URL);
    }
}
