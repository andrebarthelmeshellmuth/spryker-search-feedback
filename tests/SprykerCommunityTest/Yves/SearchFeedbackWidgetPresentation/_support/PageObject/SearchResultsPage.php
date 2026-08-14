<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\PageObject;

class SearchResultsPage
{
    /**
     * ~56 hits in this demoshop's catalog — the reliable baseline query the sibling suites also use.
     *
     * @var string
     */
    public const URL_CHAIR = '/en/search?q=chair';

    /**
     * @var string
     */
    public const SELECTOR_FORM = '.js-search-feedback-ticket-form__form';

    /**
     * @var string
     */
    public const SELECTOR_TOPIC_SELECT = '#js-search-feedback-ticket-form__topic';

    /**
     * @var string
     */
    public const SELECTOR_BODY_TEXTAREA = '#js-search-feedback-ticket-form__body';

    /**
     * @var string
     */
    public const SUBMIT_BUTTON_TEXT = 'Send Feedback';

    /**
     * @var string
     */
    public const FLASH_MESSAGE_SUCCESS = 'Thanks — your feedback ticket was submitted.';

    /**
     * Owned by the sibling spryker-community/search-debug package's overlay, not this one — present in
     * this demoshop because search-debug is installed alongside search-feedback. Used by
     * FrozenReplayCest to read the "Relevance weight (α)" line search-ranking's optional debug-data
     * expander plugin contributes to that same overlay.
     *
     * @var string
     */
    public const SELECTOR_SCORE_TRIGGER = '.search-debug-trigger';

    /**
     * @var string
     */
    public const SELECTOR_OVERLAY = '.search-debug-overlay';
}
