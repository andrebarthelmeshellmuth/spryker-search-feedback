<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerCommunity\Shared\SearchFeedbackGui\SearchFeedbackGuiConstants;

class SearchFeedbackGuiConfig extends AbstractBundleConfig
{
    /**
     * Matches `SprykerShop\Yves\CatalogPage\Plugin\Router\CatalogPageRouteProviderPlugin::ROUTE_NAME_SEARCH`
     * — kept as a local string rather than a hard composer dependency on that package, same standalone
     * posture the rest of this package already keeps toward the shop it's installed into.
     *
     * @var string
     */
    protected const SEARCH_PATH = '/search';

    /**
     * Used for a shop with dynamic store mode OFF (per-store Yves hosts) — see
     * `Spryker\Shared\AvailabilityNotification\AvailabilityNotificationConstants::STORE_TO_YVES_HOST_MAPPING`
     * for the same, already-established convention this mirrors.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getStoreToYvesHostMapping(): array
    {
        return $this->get(SearchFeedbackGuiConstants::STORE_TO_YVES_HOST_MAPPING, []);
    }

    /**
     * Used for a shop with dynamic store mode ON — every store shares this one Yves host, with the store
     * itself carried as a URL path segment instead (see `StorePrefixRouterEnhancerPlugin`).
     *
     * @api
     */
    public function getBaseUrlYves(): string
    {
        $config = $this->getConfig();

        if ($config->hasKey(ApplicationConstants::BASE_URL_YVES)) {
            return $config->get(ApplicationConstants::BASE_URL_YVES);
        }

        return '';
    }

    /**
     * @api
     */
    public function getSearchPath(): string
    {
        return static::SEARCH_PATH;
    }
}
