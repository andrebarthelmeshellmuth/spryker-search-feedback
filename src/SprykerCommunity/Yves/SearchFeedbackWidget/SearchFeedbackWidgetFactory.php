<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget;

use Spryker\Yves\Kernel\AbstractFactory;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToCustomerClientInterface;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToSearchFeedbackClientInterface;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToStoreClientInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SearchFeedbackWidgetFactory extends AbstractFactory
{
    public function getSearchFeedbackClient(): SearchFeedbackWidgetToSearchFeedbackClientInterface
    {
        return $this->getProvidedDependency(SearchFeedbackWidgetDependencyProvider::CLIENT_SEARCH_FEEDBACK);
    }

    public function getCustomerClient(): SearchFeedbackWidgetToCustomerClientInterface
    {
        return $this->getProvidedDependency(SearchFeedbackWidgetDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getStoreClient(): SearchFeedbackWidgetToStoreClientInterface
    {
        return $this->getProvidedDependency(SearchFeedbackWidgetDependencyProvider::CLIENT_STORE);
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(SearchFeedbackWidgetDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }
}
