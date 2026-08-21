<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGui\Communication\Controller;

use Codeception\Test\Unit;
use ReflectionMethod;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller\IndexController;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\SearchFeedbackGuiCommunicationFactory;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table\TicketTable;
use Symfony\Component\HttpFoundation\Request;

/**
 * `resolveStoreName()`/`resolveLocaleName()` are `protected` and touch nothing but the given `Request` —
 * no factory/app-context dependency at all, so driven directly via Reflection. `indexAction()`/
 * `tableAction()` are covered below the same way this package's sibling test files already override
 * `getFactory()` on an anonymous subclass: a mocked `TicketTable` stands in for the real one (which DOES
 * need the full Zed Silex app bootstrap to construct — see `TicketTableTest`'s own docblock — but that is
 * a fact about testing `TicketTable` ITSELF, not about testing this controller's own orchestration of it),
 * so `TicketTable`'s own `render()`/`fetchData()` behavior stays covered end-to-end by the Zed GUI
 * Presentation suite (`PreFlightCest`, `TicketGridAndDetailCest`) as before.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGui
 * @group Communication
 * @group Controller
 * @group IndexControllerTest
 * @group Portable
 */
class IndexControllerTest extends Unit
{
    public function testResolveStoreNameReturnsNullWhenTheQueryParamIsMissing(): void
    {
        $storeName = $this->invokeProtected('resolveStoreName', [Request::create('/search-feedback-gui')]);

        $this->assertNull($storeName);
    }

    public function testResolveStoreNameReturnsTheGivenStoreName(): void
    {
        $storeName = $this->invokeProtected('resolveStoreName', [Request::create('/search-feedback-gui?storeName=DE')]);

        $this->assertSame('DE', $storeName);
    }

    public function testResolveLocaleNameReturnsNullWhenTheQueryParamIsMissing(): void
    {
        $localeName = $this->invokeProtected('resolveLocaleName', [Request::create('/search-feedback-gui')]);

        $this->assertNull($localeName);
    }

    public function testResolveLocaleNameReturnsTheGivenLocaleName(): void
    {
        $localeName = $this->invokeProtected('resolveLocaleName', [Request::create('/search-feedback-gui?localeName=de_DE')]);

        $this->assertSame('de_DE', $localeName);
    }

    public function testIndexActionBuildsTheViewResponseFromTheFactory(): void
    {
        $ticketTableMock = $this->createMock(TicketTable::class);
        $ticketTableMock->method('render')->willReturn('<table>rendered ticket table</table>');

        $factoryMock = $this->createMock(SearchFeedbackGuiCommunicationFactory::class);
        $factoryMock->method('createTicketTable')->with('DE', 'de_DE')->willReturn($ticketTableMock);
        $factoryMock->method('getAllStoreNames')->willReturn(['DE', 'AT']);
        $factoryMock->method('getAllLocaleNames')->willReturn(['de_DE', 'de_AT']);

        $controller = $this->buildControllerWithFactory($factoryMock);

        $result = $controller->indexAction(Request::create('/search-feedback-gui?storeName=DE&localeName=de_DE'));

        $this->assertSame('<table>rendered ticket table</table>', $result['ticketTable']);
        $this->assertSame(['DE', 'AT'], $result['stores']);
        $this->assertSame(['de_DE', 'de_AT'], $result['locales']);
        $this->assertSame('DE', $result['selectedStoreName']);
        $this->assertSame('de_DE', $result['selectedLocaleName']);
    }

    public function testTableActionReturnsTheFetchedDataAsJson(): void
    {
        $ticketTableMock = $this->createMock(TicketTable::class);
        $ticketTableMock->method('fetchData')->willReturn(['data' => [], 'recordsTotal' => 0]);

        $factoryMock = $this->createMock(SearchFeedbackGuiCommunicationFactory::class);
        $factoryMock->method('createTicketTable')->willReturn($ticketTableMock);

        $controller = $this->buildControllerWithFactory($factoryMock);

        $jsonResponse = $controller->tableAction(Request::create('/search-feedback-gui/table'));

        $this->assertSame('{"data":[],"recordsTotal":0}', $jsonResponse->getContent());
    }

    protected function buildControllerWithFactory(SearchFeedbackGuiCommunicationFactory $factoryMock): IndexController
    {
        return new class ($factoryMock) extends IndexController {
            public function __construct(protected SearchFeedbackGuiCommunicationFactory $injectedFactory)
            {
            }

            protected function getFactory(): SearchFeedbackGuiCommunicationFactory
            {
                return $this->injectedFactory;
            }
        };
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    protected function invokeProtected(string $method, array $arguments)
    {
        $reflectionMethod = new ReflectionMethod(IndexController::class, $method);

        return $reflectionMethod->invokeArgs(new IndexController(), $arguments);
    }
}
