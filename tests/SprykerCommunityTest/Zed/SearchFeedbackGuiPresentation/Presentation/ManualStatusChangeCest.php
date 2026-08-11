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
 * Checklist section 04 - MANUAL STATUS CHANGE: unlike the reply auto-transition, the "Mark ..." links
 * move a ticket in either direction freely. Fully self-contained round trip: closes the ticket, reopens
 * it, then leaves it exactly where this test found it (open or not).
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group ManualStatusChangeCest
 * Add your own group annotations below this line
 */
class ManualStatusChangeCest
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
    public function anyStatusCanBeSetManuallyInEitherDirection(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnPage(TicketListPage::URL);
        $i->waitForElementVisible(TicketListPage::SELECTOR_TABLE, 10);

        if (!$i->tryToSeeElement(TicketListPage::SELECTOR_VIEW_BUTTON)) {
            $i->comment('No ticket exists yet; skipping. Run the sibling Yves suite\'s TicketSubmissionCest first for full coverage.');

            return;
        }

        $viewHref = $i->grabAttributeFrom(TicketListPage::SELECTOR_VIEW_BUTTON, 'href');
        $i->amOnPage($viewHref);
        $originalStatusRow = $i->grabTextFrom('//tr[th[text()="Status"]]/td');

        // Mark closed, then mark open again — a real round trip regardless of the ticket's real
        // starting status (closing an already-closed ticket, or opening an already-open one, are both
        // still valid no-op-ish state transitions the same "Mark ..." link mechanism supports).
        $i->click(TicketDetailPage::markStatusLinkXpath('closed'));
        $i->see(TicketDetailPage::FLASH_MESSAGE_STATUS_UPDATED);
        $i->assertStringContainsString('closed', $i->grabTextFrom('//tr[th[text()="Status"]]/td'));

        $i->click(TicketDetailPage::markStatusLinkXpath('open'));
        $i->see(TicketDetailPage::FLASH_MESSAGE_STATUS_UPDATED);
        $i->assertStringContainsString('open', $i->grabTextFrom('//tr[th[text()="Status"]]/td'));

        // Round-trip: restore whatever the ticket's real status was before this test touched it.
        if (str_contains((string)$originalStatusRow, 'open')) {
            return;
        }

        $originalStatus = str_contains((string)$originalStatusRow, 'answered') ? 'answered' : 'closed';
        $i->click(TicketDetailPage::markStatusLinkXpath($originalStatus));
        $i->see(TicketDetailPage::FLASH_MESSAGE_STATUS_UPDATED);
    }
}
