<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Provider;

use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;

/**
 * `search-feedback-tickets` only exposes a `Post` operation. `AbstractProvider::provide()` already
 * short-circuits `Post` to `null` before either `provideItem()` or `provideCollection()` would be
 * called (there is nothing to load — the Processor builds the response from the write itself), so
 * this class intentionally has no method overrides. A `provider` class is still required by the
 * resource schema regardless of operation shape.
 */
class SearchFeedbackTicketsStorefrontProvider extends AbstractStorefrontProvider
{
}
