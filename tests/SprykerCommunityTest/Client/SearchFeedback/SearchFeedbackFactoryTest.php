<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback;

use Codeception\Test\Unit;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackDependencyProvider;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory;
use SprykerCommunity\Client\SearchFeedback\Zed\SearchFeedbackStubInterface;

/**
 * Smoke test, one per `create*()`/`get*()` method: every method is called and the return type (or, for a
 * plain DI passthrough, the exact container value) is asserted. Cheap insurance against a wrong
 * constructor-argument count/order slipping through unnoticed — see the sibling search-debug package's own
 * `SearchDebugFactoryTest` docblock for the real bug class this catches.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group SearchFeedbackFactoryTest
 */
class SearchFeedbackFactoryTest extends Unit
{
    public function testCreateSearchFeedbackStubReturnsASearchFeedbackStub(): void
    {
        $this->assertInstanceOf(SearchFeedbackStubInterface::class, $this->createFactory()->createSearchFeedbackStub());
    }

    public function testGetZedRequestClientReturnsTheContainerValue(): void
    {
        $zedRequestClientMock = $this->createMock(SearchFeedbackToZedRequestInterface::class);

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST, $zedRequestClientMock);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $this->assertSame($zedRequestClientMock, $factory->getZedRequestClient());
    }

    protected function createFactory(): SearchFeedbackFactory
    {
        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST, $this->createMock(SearchFeedbackToZedRequestInterface::class));

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        return $factory;
    }
}
