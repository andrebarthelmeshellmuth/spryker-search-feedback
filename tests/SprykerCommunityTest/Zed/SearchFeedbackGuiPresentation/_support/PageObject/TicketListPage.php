<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PageObject;

class TicketListPage
{
    /**
     * @var string
     */
    public const URL = '/search-feedback-gui';

    /**
     * @var string
     */
    public const SELECTOR_TABLE = '.dataTable';

    /**
     * @var string
     */
    public const SELECTOR_VIEW_BUTTON = '.btn-view';
}
