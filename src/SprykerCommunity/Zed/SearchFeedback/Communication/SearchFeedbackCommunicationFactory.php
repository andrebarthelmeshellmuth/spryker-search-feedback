<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerCommunity\Zed\SearchFeedback\Communication\Authorization\CompanyUserPermissionAuthorizer;
use SprykerCommunity\Zed\SearchFeedback\Communication\Authorization\CompanyUserPermissionAuthorizerInterface;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\SearchFeedbackDependencyProvider;

class SearchFeedbackCommunicationFactory extends AbstractCommunicationFactory
{
    public function getCompanyUserFacade(): SearchFeedbackToCompanyUserFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackDependencyProvider::FACADE_COMPANY_USER);
    }

    public function getPermissionFacade(): SearchFeedbackToPermissionFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackDependencyProvider::FACADE_PERMISSION);
    }

    public function createCompanyUserPermissionAuthorizer(): CompanyUserPermissionAuthorizerInterface
    {
        return new CompanyUserPermissionAuthorizer(
            $this->getCompanyUserFacade(),
            $this->getPermissionFacade(),
        );
    }
}
