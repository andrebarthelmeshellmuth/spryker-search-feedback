<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Authorization;

use Generated\Shared\Transfer\CustomerTransfer;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;

/**
 * Copied from search-ranking-optimizer's own class of the same name and purpose — this package has no
 * dependency on that one, so the pattern is duplicated rather than shared.
 */
class CompanyUserPermissionAuthorizer implements CompanyUserPermissionAuthorizerInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeInterface $companyUserFacade
     * @param \SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface $permissionFacade
     */
    public function __construct(
        protected SearchFeedbackToCompanyUserFacadeInterface $companyUserFacade,
        protected SearchFeedbackToPermissionFacadeInterface $permissionFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param string $customerReference
     * @param string $permissionKey
     */
    public function isAuthorized(string $customerReference, string $permissionKey): bool
    {
        $customerTransfer = (new CustomerTransfer())->setCustomerReference($customerReference);
        $companyUserCollectionTransfer = $this->companyUserFacade->getActiveCompanyUsersByCustomerReference($customerTransfer);

        foreach ($companyUserCollectionTransfer->getCompanyUsers() as $companyUserTransfer) {
            if ($this->permissionFacade->can($permissionKey, $companyUserTransfer->getIdCompanyUserOrFail())) {
                return true;
            }
        }

        return false;
    }
}
