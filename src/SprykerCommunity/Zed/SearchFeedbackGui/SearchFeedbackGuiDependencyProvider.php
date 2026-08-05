<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToSearchFeedbackFacadeBridge;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToUserFacadeBridge;

class SearchFeedbackGuiDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_SEARCH_FEEDBACK = 'FACADE_SEARCH_FEEDBACK';

    /**
     * @var string
     */
    public const FACADE_USER = 'FACADE_USER';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    #[\Override]
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addSearchFeedbackFacade($container);
        $container = $this->addUserFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    protected function addSearchFeedbackFacade(Container $container): Container
    {
        $container->set(static::FACADE_SEARCH_FEEDBACK, fn (Container $container) => new SearchFeedbackGuiToSearchFeedbackFacadeBridge(
            $container->getLocator()->searchFeedback()->facade(),
        ));

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     */
    protected function addUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_USER, fn (Container $container) => new SearchFeedbackGuiToUserFacadeBridge(
            $container->getLocator()->user()->facade(),
        ));

        return $container;
    }
}
