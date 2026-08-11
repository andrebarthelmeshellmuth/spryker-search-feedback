<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade;

use Generated\Shared\Transfer\CustomerResponseTransfer;

interface SearchFeedbackGuiToCustomerFacadeInterface
{
    /**
     * @param string $customerReference
     */
    public function findCustomerByReference(string $customerReference): CustomerResponseTransfer;
}
