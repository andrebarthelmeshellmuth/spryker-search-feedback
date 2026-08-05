<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\Presentation;

use SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\PageObject\SearchResultsPage;
use SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester;

/**
 * Checklist section 02 - SUBMIT A TICKET FROM THE STOREFRONT: the real Yves round trip. Unlike the
 * sibling packages' own widgets, this package ships no delete action and this demoshop's own convention
 * is to leave test/demo tickets in the grid rather than clean them up (checklist section 08-2) — so,
 * deliberately, nothing here un-submits what it creates.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidgetPresentation
 * @group Presentation
 * @group TicketSubmissionCest
 * Add your own group annotations below this line
 */
class TicketSubmissionCest
{
    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function _before(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->amYves();
        $i->loginAsCustomer(SearchFeedbackWidgetPresentationTester::PERMITTED_CUSTOMER_EMAIL);
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function submittingTheFormRedirectsBackWithASuccessMessage(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_FORM, 10);

        $i->selectOption(SearchResultsPage::SELECTOR_TOPIC_SELECT, 'Relevance');
        $i->fillField(
            SearchResultsPage::SELECTOR_BODY_TEXTAREA,
            'Automated Presentation-suite check: stacking chairs rank low for "chair". Contains a literal & and < to double as an escaping smoke check on the Zed side.',
        );
        $i->click(SearchResultsPage::SUBMIT_BUTTON_TEXT);

        $i->seeInCurrentUrl('/en/search');
        $i->see(SearchResultsPage::FLASH_MESSAGE_SUCCESS);
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function blankBodyIsRejectedClientSide(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_FORM, 10);

        $i->click(SearchResultsPage::SUBMIT_BUTTON_TEXT);

        // The browser's native `required` validation blocks the submit - never leaves this page, and
        // the success flash from the OTHER test in this class never appears.
        $i->seeInCurrentUrl(SearchResultsPage::URL_CHAIR);
        $i->dontSee(SearchResultsPage::FLASH_MESSAGE_SUCCESS);
    }
}
