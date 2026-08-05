<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToCustomerClientBridge;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToSearchFeedbackClientBridge;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToStoreClientBridge;

class SearchFeedbackWidgetDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_SEARCH_FEEDBACK = 'CLIENT_SEARCH_FEEDBACK';

    /**
     * @var string
     */
    public const CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    /**
     * @var string
     */
    public const CLIENT_STORE = 'CLIENT_STORE';

    /**
     * @var string
     */
    public const SERVICE_FORM_CSRF_PROVIDER = 'form.csrf_provider';

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     */
    #[\Override]
    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addSearchFeedbackClient($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addStoreClient($container);
        $container = $this->addCsrfTokenManager($container);

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     */
    protected function addSearchFeedbackClient(Container $container): Container
    {
        $container->set(static::CLIENT_SEARCH_FEEDBACK, fn (Container $container) => new SearchFeedbackWidgetToSearchFeedbackClientBridge(
            $container->getLocator()->searchFeedback()->client(),
        ));

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     */
    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, fn (Container $container) => new SearchFeedbackWidgetToCustomerClientBridge(
            $container->getLocator()->customer()->client(),
        ));

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     */
    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, fn (Container $container) => new SearchFeedbackWidgetToStoreClientBridge(
            $container->getLocator()->store()->client(),
        ));

        return $container;
    }

    /**
     * Same `form.csrf_provider` application service search-ranking-optimizer's own widget uses for its
     * own non-Form POST endpoint — requires `Spryker\Yves\Form\Plugin\Application\FormApplicationPlugin`
     * registered in the project's `ShopApplicationDependencyProvider` (already true in this shop).
     *
     * @param \Spryker\Yves\Kernel\Container $container
     */
    protected function addCsrfTokenManager(Container $container): Container
    {
        $container->set(static::SERVICE_FORM_CSRF_PROVIDER, fn (Container $container) => $container->getApplicationService(static::SERVICE_FORM_CSRF_PROVIDER));

        return $container;
    }
}
