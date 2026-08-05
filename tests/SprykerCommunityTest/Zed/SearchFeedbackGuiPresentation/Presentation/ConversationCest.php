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
 * Checklist sections 03 (REPLY + AUTO STATUS TRANSITION) and 07 (DATA MODEL & ESCAPING). Both need any
 * one existing ticket - gracefully skip if the grid is empty. Section 03's two checklist cards (reply
 * moves an Open ticket forward / never moves an Answered-or-Closed one) are covered by ONE test here
 * that reads the ticket's real starting status and asserts whichever rule actually applies, rather than
 * requiring two separate tickets in two different starting states to exist.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group ConversationCest
 * Add your own group annotations below this line
 */
class ConversationCest
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
     *
     * @return string|null the ticket detail page href, or null if the grid is empty
     */
    protected function firstTicketDetailHref(SearchFeedbackGuiPresentationTester $i): ?string
    {
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible(TicketListPage::SELECTOR_TABLE, 10);

        if (!$i->tryToSeeElement(TicketListPage::SELECTOR_VIEW_BUTTON)) {
            return null;
        }

        return $i->grabAttributeFrom(TicketListPage::SELECTOR_VIEW_BUTTON, 'href');
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     */
    public function replyingMovesAnOpenTicketForwardButNeverMovesAnAnsweredOrClosedOne(SearchFeedbackGuiPresentationTester $i): void
    {
        $detailHref = $this->firstTicketDetailHref($i);

        if ($detailHref === null) {
            $i->comment('No ticket exists yet; skipping. Run the sibling Yves suite\'s TicketSubmissionCest first for full coverage.');

            return;
        }

        $i->amOnPage($detailHref);
        $statusRow = $i->grabTextFrom('//tr[th[text()="Status"]]/td');
        $wasOpen = str_contains($statusRow, 'open');

        $i->fillField('#' . TicketDetailPage::FIELD_REPLY_BODY, 'Automated Presentation-suite reply — checking the status-transition rule.');
        $i->click(TicketDetailPage::POST_REPLY_BUTTON_TEXT);
        $i->see(TicketDetailPage::FLASH_MESSAGE_REPLY_POSTED);
        $i->see('Zed:');

        $newStatusRow = $i->grabTextFrom('//tr[th[text()="Status"]]/td');

        if ($wasOpen) {
            $i->assertStringContainsString('answered', $newStatusRow, 'Expected a reply to move an Open ticket to Answered.');
        } else {
            $i->assertSame($statusRow, $newStatusRow, 'Expected a reply to leave an already-Answered/Closed ticket\'s status untouched.');
        }
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     */
    public function replyBodyContainingMarkupRendersAsLiteralTextNeverExecutes(SearchFeedbackGuiPresentationTester $i): void
    {
        $detailHref = $this->firstTicketDetailHref($i);

        if ($detailHref === null) {
            $i->comment('No ticket exists yet; skipping. Run the sibling Yves suite\'s TicketSubmissionCest first for full coverage.');

            return;
        }

        $i->amOnPage($detailHref);
        $i->fillField('#' . TicketDetailPage::FIELD_REPLY_BODY, '<script>alert(1)</script> & <b>bold</b>');
        $i->click(TicketDetailPage::POST_REPLY_BUTTON_TEXT);
        $i->see(TicketDetailPage::FLASH_MESSAGE_REPLY_POSTED);

        // Rendered as literal visible text (Codeception's see() checks rendered/visible text) - if this
        // had been interpreted as real markup instead of escaped, this exact string would NOT appear as
        // plain text (a real <script> tag has no visible text content, and a real <b> would bold the
        // word rather than show the tag characters themselves).
        $i->see('<script>alert(1)</script> & <b>bold</b>');
    }
}
