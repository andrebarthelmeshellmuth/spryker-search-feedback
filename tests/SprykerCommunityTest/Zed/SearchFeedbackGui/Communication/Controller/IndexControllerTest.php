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
use Symfony\Component\HttpFoundation\Request;

/**
 * `resolveStoreName()`/`resolveLocaleName()` are `protected` and touch nothing but the given `Request` —
 * no factory/app-context dependency at all, so driven directly via Reflection. `indexAction()`/
 * `tableAction()` themselves are NOT covered here: both resolve `createTicketTable(...)->render()`/
 * `->fetchData()`, which need the full Zed Silex app bootstrap (see `TicketTableTest`'s own docblock) —
 * already covered end-to-end by the Zed GUI Presentation suite (`PreFlightCest`, `TicketGridAndDetailCest`).
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
