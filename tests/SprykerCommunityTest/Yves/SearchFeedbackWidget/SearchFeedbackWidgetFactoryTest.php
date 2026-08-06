<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidget;

use Codeception\Test\Unit;
use Spryker\Yves\Kernel\Container;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToCustomerClientInterface;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToSearchFeedbackClientInterface;
use SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client\SearchFeedbackWidgetToStoreClientInterface;
use SprykerCommunity\Yves\SearchFeedbackWidget\SearchFeedbackWidgetDependencyProvider;
use SprykerCommunity\Yves\SearchFeedbackWidget\SearchFeedbackWidgetFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Every method here is a plain DI passthrough (`getProvidedDependency()`), so the one thing worth
 * asserting is that each `get*()` returns EXACTLY the container value set under its own dependency-provider
 * constant — a copy-paste error swapping two constants would otherwise only surface as a confusing runtime
 * type error deep inside `SubmitTicketController`.
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidget
 * @group SearchFeedbackWidgetFactoryTest
 */
class SearchFeedbackWidgetFactoryTest extends Unit
{
    public function testGetSearchFeedbackClientReturnsTheContainerValue(): void
    {
        $clientMock = $this->createMock(SearchFeedbackWidgetToSearchFeedbackClientInterface::class);
        $factory = $this->createFactory([SearchFeedbackWidgetDependencyProvider::CLIENT_SEARCH_FEEDBACK => $clientMock]);

        $this->assertSame($clientMock, $factory->getSearchFeedbackClient());
    }

    public function testGetCustomerClientReturnsTheContainerValue(): void
    {
        $clientMock = $this->createMock(SearchFeedbackWidgetToCustomerClientInterface::class);
        $factory = $this->createFactory([SearchFeedbackWidgetDependencyProvider::CLIENT_CUSTOMER => $clientMock]);

        $this->assertSame($clientMock, $factory->getCustomerClient());
    }

    public function testGetStoreClientReturnsTheContainerValue(): void
    {
        $clientMock = $this->createMock(SearchFeedbackWidgetToStoreClientInterface::class);
        $factory = $this->createFactory([SearchFeedbackWidgetDependencyProvider::CLIENT_STORE => $clientMock]);

        $this->assertSame($clientMock, $factory->getStoreClient());
    }

    public function testGetCsrfTokenManagerReturnsTheContainerValue(): void
    {
        $csrfTokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);
        $factory = $this->createFactory([SearchFeedbackWidgetDependencyProvider::SERVICE_FORM_CSRF_PROVIDER => $csrfTokenManagerMock]);

        $this->assertSame($csrfTokenManagerMock, $factory->getCsrfTokenManager());
    }

    /**
     * @param array<string, mixed> $containerValues
     */
    protected function createFactory(array $containerValues): SearchFeedbackWidgetFactory
    {
        $container = new Container();

        foreach ($containerValues as $key => $value) {
            $container->set($key, $value);
        }

        $factory = new SearchFeedbackWidgetFactory();
        $factory->setContainer($container);

        return $factory;
    }
}
