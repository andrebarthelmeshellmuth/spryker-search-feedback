<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\Presentation;

use SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester;

/**
 * Checklist section 08 - REGRESSION: confirm this package didn't disturb anything outside its own two
 * tables/two Zed pages/one Yves widget. Lives in the Zed suite only for convenience (amZed() is already
 * wired up here) — the assertion itself is about the storefront, reached directly by absolute URL.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGuiPresentation
 * @group Presentation
 * @group RegressionCest
 * Add your own group annotations below this line
 */
class RegressionCest
{
    /**
     * @param \SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\SearchFeedbackGuiPresentationTester $i
     *
     * @return void
     */
    public function plainCatalogSearchStillBehavesNormally(SearchFeedbackGuiPresentationTester $i): void
    {
        $i->amOnUrl('http://yves.eu.spryker.local/en/search?q=chair');
        $i->see('Results for');
        $i->see('chair');
        // No search-debug overlay, no search-ranking-optimizer rating buttons - this package adds a
        // form, nothing more, for a logged-out guest with none of the sibling permissions.
        $i->dontSeeElement('.search-debug-trigger');
        $i->dontSeeElement('.search-ranking-optimizer-product-rating__button--heart');
    }
}
