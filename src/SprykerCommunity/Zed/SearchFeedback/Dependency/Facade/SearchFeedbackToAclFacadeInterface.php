<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Dependency\Facade;

use Generated\Shared\Transfer\GroupsTransfer;
use Generated\Shared\Transfer\RolesTransfer;
use Generated\Shared\Transfer\RulesTransfer;

/**
 * Read-only, and used ONLY by `search-feedback:check-installation` to work out whether this package's own
 * Zed pages are reachable by anybody other than a root-style admin. Nothing in this package's runtime
 * behavior depends on ACL — Zed access control is enforced entirely by Spryker's own Acl module, on the
 * request path, exactly as it is for every other Zed module.
 */
interface SearchFeedbackToAclFacadeInterface
{
    public function getAllGroups(): GroupsTransfer;

    /**
     * @param int $idGroup
     */
    public function getGroupRoles(int $idGroup): RolesTransfer;

    /**
     * @param int $idRole
     */
    public function getRoleRules(int $idRole): RulesTransfer;
}
