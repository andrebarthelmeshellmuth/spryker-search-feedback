<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchFeedbackGui;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface SearchFeedbackGuiConstants
{
    /**
     * Specification:
     * - Defines stores to Yves host mapping, for a shop with dynamic store mode OFF (per-store Yves
     *   hosts) — needed to build a working link from a ticket's Zed detail page back to the real SRP it
     *   was filed from. Same, already-established convention as
     *   `Spryker\Shared\AvailabilityNotification\AvailabilityNotificationConstants::STORE_TO_YVES_HOST_MAPPING`
     *   and `Spryker\Shared\Sitemap\SitemapConstants::STORE_TO_YVES_HOST_MAPPING`.
     *
     * @api
     *
     * @var string
     */
    public const STORE_TO_YVES_HOST_MAPPING = 'SEARCH_FEEDBACK_GUI:STORE_TO_YVES_HOST_MAPPING';
}
