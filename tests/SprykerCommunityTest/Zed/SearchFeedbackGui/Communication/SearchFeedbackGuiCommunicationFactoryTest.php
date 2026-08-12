<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGui\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Kernel\Container;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\SearchFeedbackGuiCommunicationFactory;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table\TicketTable;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToLocaleFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToSearchFeedbackFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiDependencyProvider;

/**
 * Smoke test, one per `create*()`/`get*()` method — same posture as the sibling packages' own
 * FactoryTests (see e.g. search-debug's `SearchDebugFactoryTest` docblock for the bug class this catches).
 *
 * `createReplyForm()` is deliberately NOT covered here: it resolves the real Zed Silex `form.factory`
 * application service (`AbstractCommunicationFactory::getFormFactory()`), which only exists once the full
 * Zed application is bootstrapped — not available under Codeception's `Environment` helper alone (confirmed
 * empirically: `Call to a member function create() on null`). The `ReplyForm` type itself — the only thing
 * that method's caller actually depends on behaving correctly — has full, dedicated coverage in
 * {@see \SprykerCommunityTest\Zed\SearchFeedbackGui\Communication\Form\ReplyFormTest} via a real, standalone
 * Symfony `FormFactory`.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGui
 * @group Communication
 * @group SearchFeedbackGuiCommunicationFactoryTest
 * @group Portable
 */
class SearchFeedbackGuiCommunicationFactoryTest extends Unit
{
    public function testCreateTicketTableReturnsATicketTable(): void
    {
        $this->assertInstanceOf(TicketTable::class, $this->createFactory()->createTicketTable());
    }

    public function testGetSearchFeedbackFacadeReturnsTheContainerValue(): void
    {
        $facadeMock = $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $facadeMock);
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame($facadeMock, $factory->getSearchFeedbackFacade());
    }

    public function testGetUserFacadeReturnsTheContainerValue(): void
    {
        $facadeMock = $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $facadeMock);

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame($facadeMock, $factory->getUserFacade());
    }

    public function testGetStoreFacadeReturnsTheContainerValue(): void
    {
        $facadeMock = $this->createMock(SearchFeedbackGuiToStoreFacadeInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_STORE, $facadeMock);

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame($facadeMock, $factory->getStoreFacade());
    }

    public function testGetLocaleFacadeReturnsTheContainerValue(): void
    {
        $facadeMock = $this->createMock(SearchFeedbackGuiToLocaleFacadeInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_LOCALE, $facadeMock);

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame($facadeMock, $factory->getLocaleFacade());
    }

    public function testGetAllStoreNamesReturnsTheNameOfEveryStore(): void
    {
        $storeFacadeMock = $this->createMock(SearchFeedbackGuiToStoreFacadeInterface::class);
        $storeFacadeMock->method('getAllStores')->willReturn([
            (new StoreTransfer())->setName('DE'),
            (new StoreTransfer())->setName('AT'),
        ]);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_STORE, $storeFacadeMock);

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame(['DE', 'AT'], $factory->getAllStoreNames());
    }

    public function testGetAllLocaleNamesReturnsEveryAvailableLocaleName(): void
    {
        $localeFacadeMock = $this->createMock(SearchFeedbackGuiToLocaleFacadeInterface::class);
        $localeFacadeMock->method('getAvailableLocales')->willReturn([1 => 'de_DE', 2 => 'en_US']);

        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_LOCALE, $localeFacadeMock);

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        $this->assertSame(['de_DE', 'en_US'], $factory->getAllLocaleNames());
    }

    protected function createFactory(): SearchFeedbackGuiCommunicationFactory
    {
        $container = new Container();
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK, $this->createMock(SearchFeedbackGuiToSearchFeedbackFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_USER, $this->createMock(SearchFeedbackGuiToUserFacadeInterface::class));
        $container->set(SearchFeedbackGuiDependencyProvider::FACADE_CUSTOMER, $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class));

        $factory = new SearchFeedbackGuiCommunicationFactory();
        $factory->setContainer($container);

        return $factory;
    }
}
