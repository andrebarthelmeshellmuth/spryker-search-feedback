<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeBridge;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeBridge;

class SearchFeedbackDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * Used by {@see \SprykerCommunity\Zed\SearchFeedback\Communication\Authorization\CompanyUserPermissionAuthorizer}
     * to resolve a Yves customerReference to every active CompanyUser it maps to.
     *
     * @var string
     */
    public const FACADE_COMPANY_USER = 'FACADE_COMPANY_USER';

    /**
     * Re-checks SubmitSearchFeedbackTicketPermissionPlugin server-side in the Gateway Controller,
     * independently of anything the Yves side asserted.
     *
     * @var string
     */
    public const FACADE_PERMISSION = 'FACADE_PERMISSION';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    #[\Override]
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addCompanyUserFacade($container);
        $container = $this->addPermissionFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    protected function addCompanyUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_USER, fn (Container $container) => new SearchFeedbackToCompanyUserFacadeBridge(
            $container->getLocator()->companyUser()->facade(),
        ));

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    protected function addPermissionFacade(Container $container): Container
    {
        $container->set(static::FACADE_PERMISSION, fn (Container $container) => new SearchFeedbackToPermissionFacadeBridge(
            $container->getLocator()->permission()->facade(),
        ));

        return $container;
    }
}
