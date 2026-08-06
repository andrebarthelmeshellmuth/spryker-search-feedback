<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestBridge;

class SearchFeedbackDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_ZED_REQUEST = 'CLIENT_ZED_REQUEST';

    /**
     * @param \Spryker\Client\Kernel\Container $container
     */
    #[\Override]
    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);

        return $this->addZedRequestClient($container);
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     */
    protected function addZedRequestClient(Container $container): Container
    {
        $container->set(static::CLIENT_ZED_REQUEST, fn (Container $container) => new SearchFeedbackToZedRequestBridge($container->getLocator()->zedRequest()->client()));

        return $container;
    }
}
