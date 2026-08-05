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
 * Checklist section 06 - PERMISSION NEGATIVE TEST: only the granted role sees the form, checked both
 * client-side (the Twig gate) and re-checked independently server-side.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidgetPresentation
 * @group Presentation
 * @group PermissionGateCest
 * Add your own group annotations below this line
 */
class PermissionGateCest
{
    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function _before(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->amYves();
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function anonymousShopperSeesNoTicketForm(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->dontSeeElement(SearchResultsPage::SELECTOR_FORM);
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function loggedInCustomerWithoutTheRoleSeesNoTicketForm(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->loginAsCustomer(SearchFeedbackWidgetPresentationTester::UNPERMITTED_CUSTOMER_EMAIL);
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->dontSeeElement(SearchResultsPage::SELECTOR_FORM);
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function permittedCustomerDoesSeeIt(SearchFeedbackWidgetPresentationTester $i): void
    {
        // Positive control for the two negative tests above.
        $i->loginAsCustomer(SearchFeedbackWidgetPresentationTester::PERMITTED_CUSTOMER_EMAIL);
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_FORM, 10);
    }
}
