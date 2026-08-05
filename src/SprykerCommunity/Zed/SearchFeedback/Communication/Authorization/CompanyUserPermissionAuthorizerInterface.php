<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Authorization;

interface CompanyUserPermissionAuthorizerInterface
{
    /**
     * @param string $customerReference
     * @param string $permissionKey
     */
    public function isAuthorized(string $customerReference, string $permissionKey): bool;
}
