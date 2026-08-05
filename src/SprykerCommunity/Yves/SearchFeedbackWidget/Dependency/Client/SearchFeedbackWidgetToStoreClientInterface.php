<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client;

use Generated\Shared\Transfer\StoreTransfer;

interface SearchFeedbackWidgetToStoreClientInterface
{
    public function getCurrentStore(): StoreTransfer;
}
