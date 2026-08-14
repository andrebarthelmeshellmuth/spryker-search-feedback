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
 * Real regression test for a live bug found and fixed on 2026-08-14: a ticket's "View SRP" replay
 * showed the correct frozen PRODUCT ORDER, but the debug overlay's "Relevance weight (α)" value
 * tracked whatever search-ranking's live setting currently was, not the one that actually scored the
 * ticket at filing time — because query expansion recomputes that number fresh on every request,
 * replay or not, and nothing restored it. Fixed by
 * {@see \SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotRestorerPluginInterface}
 * + search-ranking's own registered implementation.
 *
 * This test is only meaningful, not trivially green, because it changes the LIVE setting BETWEEN
 * capturing the "before" value and reading the replay — a version of this test that never touched the
 * live setting would pass identically whether or not the underlying bug was ever fixed.
 *
 * Requires search-debug AND search-ranking installed alongside search-feedback (this demoshop has all
 * three) — the overlay itself, and the "Relevance weight (α)" line inside it, are both owned by those
 * sibling packages, not this one.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidgetPresentation
 * @group Presentation
 * @group FrozenReplayCest
 * Add your own group annotations below this line
 */
class FrozenReplayCest
{
    /**
     * The company_user_role.csv-granted customer that already holds both
     * SubmitSearchFeedbackTicketPermissionPlugin and ViewSearchFeedbackTicketReplayPermissionPlugin via
     * test-company_Admin — same account
     * {@see SearchFeedbackWidgetPresentationTester::PERMITTED_CUSTOMER_EMAIL} resolves to, named
     * explicitly here because the DB lookups need the raw reference, not the login email.
     *
     * @var string
     */
    protected const CUSTOMER_REFERENCE = 'SearchAdmin--1';

    /**
     * @var string
     */
    protected const STORE_NAME = 'DE';

    /**
     * @var string
     */
    protected const LOCALE_NAME = 'en_US';

    /**
     * Restored in _after() regardless of which assertion (if any) fails, so a red run never leaves the
     * demoshop's live ranking weight altered for the next suite/manual session.
     *
     * @var string
     */
    protected const ORIGINAL_RELEVANCE_WEIGHT = 0.75;

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
    public function _after(SearchFeedbackWidgetPresentationTester $i): void
    {
        $i->setSearchRankingRelevanceWeight(static::STORE_NAME, static::LOCALE_NAME, static::ORIGINAL_RELEVANCE_WEIGHT);
        $i->publishSearchRankingSettings();
    }

    /**
     * @param \SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\SearchFeedbackWidgetPresentationTester $i
     */
    public function replayedOverlayKeepsTheRelevanceWeightThatScoredTheTicketEvenAfterTheLiveWeightChanges(
        SearchFeedbackWidgetPresentationTester $i,
    ): void {
        // Arrange: a known "before" weight, published all the way to the key-value store the live
        // query-expansion plugin actually reads from.
        $i->setSearchRankingRelevanceWeight(static::STORE_NAME, static::LOCALE_NAME, 0.90);
        $i->publishSearchRankingSettings();

        // Act 1: load the results page under the "before" weight and read the overlay's displayed α.
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_SCORE_TRIGGER, 10);
        $i->click(SearchResultsPage::SELECTOR_SCORE_TRIGGER);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_OVERLAY, 5);
        $overlayTextBeforeCapture = $i->grabTextFrom(SearchResultsPage::SELECTOR_OVERLAY);
        $alphaBeforeCapture = static::extractRelevanceWeight($overlayTextBeforeCapture);
        $i->assertNotNull($alphaBeforeCapture, 'Could not find a "Relevance weight (α)" line in the overlay — is search-ranking installed and its debug-data expander plugin registered?');

        // Act 2: submit the ticket against this exact page load, capturing this exact α in its snapshot.
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_FORM, 10);
        $i->selectOption(SearchResultsPage::SELECTOR_TOPIC_SELECT, 'Relevance');
        $i->fillField(
            SearchResultsPage::SELECTOR_BODY_TEXTAREA,
            'Automated Presentation-suite check: frozen-replay alpha regression test.',
        );
        $i->scrollTo("//button[contains(., '" . SearchResultsPage::SUBMIT_BUTTON_TEXT . "')]", 0, -150);
        $i->click(SearchResultsPage::SUBMIT_BUTTON_TEXT);
        $i->see(SearchResultsPage::FLASH_MESSAGE_SUCCESS);

        $idSearchFeedbackTicket = $i->grabLatestSearchFeedbackTicketIdForCustomerReference(static::CUSTOMER_REFERENCE);

        // Act 3: change the live weight to something clearly different, and publish it.
        $i->setSearchRankingRelevanceWeight(static::STORE_NAME, static::LOCALE_NAME, 0.10);
        $i->publishSearchRankingSettings();

        // Sanity check: a plain (non-replay) reload under the new weight must show a DIFFERENT α — this
        // is what proves the live setting change actually took effect, so the match asserted below
        // cannot be a coincidence of the weight never having changed at all.
        $i->amOnPage(SearchResultsPage::URL_CHAIR);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_SCORE_TRIGGER, 10);
        $i->click(SearchResultsPage::SELECTOR_SCORE_TRIGGER);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_OVERLAY, 5);
        $overlayTextUnderNewLiveWeight = $i->grabTextFrom(SearchResultsPage::SELECTOR_OVERLAY);
        $alphaUnderNewLiveWeight = static::extractRelevanceWeight($overlayTextUnderNewLiveWeight);
        $i->assertNotSame($alphaBeforeCapture, $alphaUnderNewLiveWeight, 'A live (non-replay) search should reflect the new relevanceWeight setting — if this fails, the setting change itself did not take effect, and the assertion below would be meaningless either way.');

        // Assert: the replay must still show the ORIGINAL α, not the new live one.
        $i->amOnPage(SearchResultsPage::URL_CHAIR . '&srpReplayTicket=' . $idSearchFeedbackTicket);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_SCORE_TRIGGER, 10);
        $i->click(SearchResultsPage::SELECTOR_SCORE_TRIGGER);
        $i->waitForElementVisible(SearchResultsPage::SELECTOR_OVERLAY, 5);
        $overlayTextDuringReplay = $i->grabTextFrom(SearchResultsPage::SELECTOR_OVERLAY);
        $alphaDuringReplay = static::extractRelevanceWeight($overlayTextDuringReplay);

        $i->assertSame($alphaBeforeCapture, $alphaDuringReplay, 'The replay\'s displayed relevance weight drifted from what actually scored the ticket — this is the exact live-leakage bug fixed on 2026-08-14.');
    }

    /**
     * @param string $overlayText
     *
     * @return string|null
     */
    protected static function extractRelevanceWeight(string $overlayText): ?string
    {
        if (preg_match('/Relevance weight \(α\):\s*(\d+\.\d+)/u', $overlayText, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
