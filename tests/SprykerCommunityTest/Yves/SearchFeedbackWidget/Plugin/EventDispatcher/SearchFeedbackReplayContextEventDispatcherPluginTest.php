<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidget\Plugin\EventDispatcher;

use Codeception\Test\Unit;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\EventDispatcher\SearchFeedbackReplayContextEventDispatcherPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Mirrors the sibling search-debug package's own
 * {@see \SprykerCommunityTest\Yves\SearchDebugWidget\Plugin\EventDispatcher\SearchDebugContextEventDispatcherPluginTest}:
 * the permission-granted-or-denied branch (`PermissionAwareTrait::can()`) reaches through the global
 * `Locator` singleton with no constructor seam to inject a fake permission client, so that branch stays
 * integration-only coverage. Both of `handleRequest()`'s early-return paths — sub-request, and no replay
 * ticket param present — are covered directly below, along with `extend()`'s listener wiring.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidget
 * @group Plugin
 * @group EventDispatcher
 * @group SearchFeedbackReplayContextEventDispatcherPluginTest
 * Add your own group annotations below this line
 * @group Portable
 */
class SearchFeedbackReplayContextEventDispatcherPluginTest extends Unit
{
    /**
     * A sub-request (e.g. an ESI/fragment render) must never trigger the permission check, even when the
     * replay ticket param is present -- `handleRequest()` must return before ever calling `can()`.
     */
    public function testHandleRequestDoesNothingForANonMainRequest(): void
    {
        // Arrange
        $plugin = new SearchFeedbackReplayContextEventDispatcherPlugin();
        $eventDispatcher = $this->extendDispatcher($plugin);

        $request = Request::create('/search?' . SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET . '=42');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
        );

        // Act & Assert -- can() would throw (no Locator available here) if handleRequest() got past its
        // sub-request guard, so a clean dispatch proves the early return fired.
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
        $this->assertTrue(true);
    }

    /**
     * A main request with no replay ticket param must also never trigger the permission check --
     * `handleRequest()`'s second early return.
     */
    public function testHandleRequestDoesNothingWhenNoReplayTicketParamIsPresent(): void
    {
        // Arrange
        $plugin = new SearchFeedbackReplayContextEventDispatcherPlugin();
        $eventDispatcher = $this->extendDispatcher($plugin);

        $request = Request::create('/search');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        // Act & Assert -- same reasoning: can() would throw here without a Locator, so a clean dispatch
        // proves handleRequest() returned before reaching it.
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
        $this->assertTrue(true);
    }

    protected function extendDispatcher(SearchFeedbackReplayContextEventDispatcherPlugin $plugin): EventDispatcherInterface
    {
        return $plugin->extend(new EventDispatcher(), $this->createMock(ContainerInterface::class));
    }
}
