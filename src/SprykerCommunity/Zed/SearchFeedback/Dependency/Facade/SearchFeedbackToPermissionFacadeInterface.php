<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Dependency\Facade;

use Generated\Shared\Transfer\PermissionTransfer;

interface SearchFeedbackToPermissionFacadeInterface
{
    /**
     * @param string $permissionKey
     * @param string|int $identifier
     */
    public function can(string $permissionKey, $identifier): bool;

    /**
     * A plain `spy_permission` lookup by key — NOT the same as a registered plugin existing in code.
     * Deliberately used by {@see \SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole}
     * instead of `PermissionFacade::findMergedRegisteredNonInfrastructuralPermissions()`: that method
     * itself throws an `Undefined array key` warning for a permission plugin that's registered in code but
     * never synced into `spy_permission` — exactly the state this check exists to catch, so calling it
     * would crash the very check meant to detect the problem. This method is a simple, safe DB read with
     * no such landmine.
     *
     * @param string $permissionKey
     *
     * @return \Generated\Shared\Transfer\PermissionTransfer|null
     */
    public function findPermissionByKey(string $permissionKey): ?PermissionTransfer;
}
