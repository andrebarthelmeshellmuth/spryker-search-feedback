<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Dependency\Facade;

interface SearchFeedbackToPermissionFacadeInterface
{
    /**
     * @param string $permissionKey
     * @param string|int $identifier
     */
    public function can(string $permissionKey, $identifier): bool;
}
