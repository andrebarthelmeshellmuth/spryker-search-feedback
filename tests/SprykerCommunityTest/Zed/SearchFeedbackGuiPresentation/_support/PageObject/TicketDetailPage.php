<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PageObject;

class TicketDetailPage
{
    /**
     * @var string
     */
    public const URL = '/search-feedback-gui/detail';

    /**
     * @var string
     */
    public const URL_CHANGE_STATUS = '/search-feedback-gui/detail/change-status';

    /**
     * @var string
     */
    public const URL_PARAM_ID = 'id-search-feedback-ticket';

    /**
     * @var string
     */
    public const FIELD_REPLY_BODY = 'search_feedback_reply_body';

    /**
     * @var string
     */
    public const POST_REPLY_BUTTON_TEXT = 'Post Reply';

    /**
     * @var string
     */
    public const FLASH_MESSAGE_REPLY_POSTED = 'Reply posted.';

    /**
     * @var string
     */
    public const FLASH_MESSAGE_STATUS_UPDATED = 'Ticket status updated.';

    /**
     * @var string
     */
    public const FLASH_MESSAGE_UNKNOWN_STATUS = 'Unknown ticket status.';

    /**
     * @var string
     */
    public const FLASH_MESSAGE_TICKET_NOT_FOUND_FORMAT = 'Ticket with id %d does not exist.';

    /**
     * @var string
     */
    public const CSRF_TOKEN_FIELD_XPATH = "//input[@name='csrfToken']";

    /**
     * changeStatusAction() is a CSRF-protected POST endpoint (see DetailController) — each status is its
     * own small `<form>`/`<button>` now, not an `<a href>` link, so a real WebDriver click still performs
     * a real POST with the server-rendered CSRF token already embedded.
     *
     * @param string $status e.g. "answered", "closed", "open"
     *
     * @return string XPath for that status's own "Mark <status>" submit button.
     */
    public static function markStatusButtonXpath(string $status): string
    {
        return sprintf(
            "//form[.//input[@name='status' and @value='%s']]//button[contains(., 'Mark')]",
            $status,
        );
    }
}
