<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidget\Controller;

use Closure;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\PermissionTransfer;
use ReflectionMethod;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use Spryker\Yves\Kernel\View\View;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\EventDispatcher\SearchFeedbackReplayContextEventDispatcherPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router\SearchFeedbackWidgetRouteProviderPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Twig\SearchFeedbackWidgetTwigPlugin;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * `indexAction()` and its container-touching helpers (`checkTwigFunctions()`, `checkRoutes()`,
 * `checkFrozenReplayEventListener()`, `isTwigFunctionCallable()`, `isRouteRegistered()`) are exercised here
 * against a minimal hand-built `ContainerInterface` fixture (a real `Twig\Environment`/`EventDispatcher`, a
 * mocked router) rather than a full Silex/Symfony application boot — `AbstractController` only ever reaches
 * its container through `getApplication()->get($id)`, so a fixture answering exactly the 3 service ids this
 * controller asks for (`twig`, `dispatcher`, `routers`) is a faithful stand-in without needing the real
 * app. `can()`/`runChecks()`/`view()` are partial-mocked where `indexAction()`'s own branching (not a
 * sub-check's internals) is what's under test. Mirrors the sibling spryker-community/search-debug
 * package's identical test for its own CheckInstallationController.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchFeedbackWidget
 * @group Controller
 * @group CheckInstallationControllerTest
 * Add your own group annotations below this line
 * @group Portable
 */
class CheckInstallationControllerTest extends Unit
{
    public function testIndexActionReturnsAForbiddenResponseWhenThePermissionIsMissing(): void
    {
        // Arrange
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['can', 'renderView'])
            ->getMock();
        $controller->method('can')->with(SubmitSearchFeedbackTicketPermissionPlugin::KEY)->willReturn(false);
        $controller->expects($this->once())
            ->method('renderView')
            ->with(
                '@SearchFeedbackWidget/views/check-installation/permission-denied.twig',
                [],
                $this->callback(fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_FORBIDDEN),
            )
            ->willReturn(new Response('', Response::HTTP_FORBIDDEN));

        // Act
        $result = $controller->indexAction();

        // Assert
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testIndexActionReturnsTheViewWithChecksWhenPermitted(): void
    {
        // Arrange
        $checks = [['label' => 'a check', 'passed' => true, 'remedy' => null]];
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['can', 'runChecks'])
            ->getMock();
        $controller->method('can')->with(SubmitSearchFeedbackTicketPermissionPlugin::KEY)->willReturn(true);
        $controller->method('runChecks')->willReturn($checks);

        // Act — view() itself is pure (inherited from AbstractController, just `new View(...)`), so it
        // runs for real here rather than being mocked too.
        $result = $controller->indexAction();

        // Assert
        $this->assertInstanceOf(View::class, $result);
        $this->assertSame(['checks' => $checks], $result->getData());
        $this->assertSame('@SearchFeedbackWidget/views/check-installation/check-installation.twig', $result->getTemplate());
    }

    public function testRunChecksReturnsAllSixChecksInOrder(): void
    {
        // Arrange — a container plus stubbed getters that pass every individual check, so the only thing
        // under test here is that all six run and land in the right order.
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET, fn () => true));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_TICKET_CSRF_TOKEN, fn () => ''));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_GET_TOPICS, fn () => []));
        $dispatcher = new EventDispatcher();
        (new SearchFeedbackReplayContextEventDispatcherPlugin())->extend($dispatcher, $this->createMock(ContainerInterface::class));
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willReturn('/some/url');

        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('getRegisteredPermissions')->willReturn(
            (new PermissionCollectionTransfer())->addPermission((new PermissionTransfer())->setKey(ViewSearchFeedbackTicketReplayPermissionPlugin::KEY)),
        );

        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getPermissionClient', 'isSearchRankingInstalled', 'getSearchResultsTemplateFilePath'])
            ->getMock();
        $controller->method('getPermissionClient')->willReturn($permissionClientMock);
        $controller->method('isSearchRankingInstalled')->willReturn(false);
        $controller->method('getSearchResultsTemplateFilePath')->willReturn('/does/not/exist.twig');
        $controller->setApplication($this->createContainer($twig, $dispatcher, $router));

        // Act
        $checks = $this->invokeProtectedMethodOn($controller, 'runChecks');

        // Assert
        $this->assertCount(6, $checks);
        $this->assertTrue($checks[0]['passed']);
        $this->assertTrue($checks[1]['passed']);
        $this->assertTrue($checks[2]['passed']);
        $this->assertTrue($checks[3]['passed']);
        $this->assertTrue($checks[4]['passed']);
        $this->assertTrue($checks[5]['passed']);
    }

    public function testCheckTwigFunctionsReturnsPassedWhenAllThreeAreRegistered(): void
    {
        // Arrange
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET, fn () => true));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_TICKET_CSRF_TOKEN, fn () => ''));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_GET_TOPICS, fn () => []));

        // Act
        $check = $this->invokeProtectedMethod('checkTwigFunctions', [], $this->createContainer($twig));

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    /**
     * `debug: true` is NOT about debugging this test — it changes `Environment::$optionsHash`, which
     * feeds directly into the generated template class name (see `Environment::getTemplateClass()`).
     * Without it, this environment would compile the SAME source strings the "all registered" test above
     * already compiled to the SAME class names, so `checkTwigFunctions()` would silently reuse those
     * already-successful classes via `class_exists()` instead of re-validating that the functions are
     * actually missing here.
     */
    public function testCheckTwigFunctionsReturnsFailedListingEveryMissingFunctionWhenSomeAreMissing(): void
    {
        // Arrange
        $twig = new Environment(new ArrayLoader(), ['debug' => true]);
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET, fn () => true));

        // Act
        $check = $this->invokeProtectedMethod('checkTwigFunctions', [], $this->createContainer($twig));

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('TwigDependencyProvider.php', $check['remedy']);
        $this->assertStringContainsString(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_TICKET_CSRF_TOKEN, $check['remedy']);
        $this->assertStringContainsString(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_GET_TOPICS, $check['remedy']);
        $this->assertStringNotContainsString(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET, $check['remedy']);
    }

    public function testIsTwigFunctionCallableReturnsTrueWhenTheFunctionCompiles(): void
    {
        // Arrange
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('someFunction', fn () => []));

        // Act
        $isCallable = $this->invokeProtectedMethod('isTwigFunctionCallable', ['someFunction'], $this->createContainer($twig));

        // Assert
        $this->assertTrue($isCallable);
    }

    public function testIsTwigFunctionCallableReturnsFalseWhenTheFunctionDoesNotExist(): void
    {
        // Arrange
        $twig = new Environment(new ArrayLoader());

        // Act
        $isCallable = $this->invokeProtectedMethod('isTwigFunctionCallable', ['notRegistered'], $this->createContainer($twig));

        // Assert
        $this->assertFalse($isCallable);
    }

    public function testCheckRoutesReturnsPassedWhenTheRouteIsRegistered(): void
    {
        // Arrange
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willReturn('/some/url');

        // Act
        $check = $this->invokeProtectedMethod('checkRoutes', [], $this->createContainer(null, null, $router));

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    public function testCheckRoutesReturnsFailedWithARemedyWhenTheRouteIsMissing(): void
    {
        // Arrange
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willThrowException(new RouteNotFoundException());

        // Act
        $check = $this->invokeProtectedMethod('checkRoutes', [], $this->createContainer(null, null, $router));

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('RouterDependencyProvider.php', $check['remedy']);
    }

    public function testIsRouteRegisteredReturnsTrueWhenTheRouterGeneratesAUrl(): void
    {
        // Arrange
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willReturn('/some/url');

        // Act
        $isRegistered = $this->invokeProtectedMethod('isRouteRegistered', [SearchFeedbackWidgetRouteProviderPlugin::ROUTE_NAME_SUBMIT_TICKET], $this->createContainer(null, null, $router));

        // Assert
        $this->assertTrue($isRegistered);
    }

    public function testIsRouteRegisteredReturnsFalseWhenTheRouterThrowsRouteNotFoundException(): void
    {
        // Arrange
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willThrowException(new RouteNotFoundException());

        // Act
        $isRegistered = $this->invokeProtectedMethod('isRouteRegistered', ['some-route'], $this->createContainer(null, null, $router));

        // Assert
        $this->assertFalse($isRegistered);
    }

    public function testCheckFrozenReplayEventListenerReturnsPassedWhenTheListenerIsRegistered(): void
    {
        // Arrange
        $dispatcher = new EventDispatcher();
        (new SearchFeedbackReplayContextEventDispatcherPlugin())->extend($dispatcher, $this->createMock(ContainerInterface::class));

        // Act
        $check = $this->invokeProtectedMethod('checkFrozenReplayEventListener', [], $this->createContainer(null, $dispatcher));

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    public function testCheckFrozenReplayEventListenerReturnsFailedWithARemedyWhenTheListenerIsNotRegistered(): void
    {
        // Arrange
        $dispatcher = new EventDispatcher();

        // Act
        $check = $this->invokeProtectedMethod('checkFrozenReplayEventListener', [], $this->createContainer(null, $dispatcher));

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('EventDispatcherDependencyProvider.php', $check['remedy']);
    }

    public function testCheckReplayPermissionRegisteredReturnsPassedWhenTheKeyIsInTheRegisteredCollection(): void
    {
        // Arrange
        $permissionCollection = (new PermissionCollectionTransfer())
            ->addPermission((new PermissionTransfer())->setKey(ViewSearchFeedbackTicketReplayPermissionPlugin::KEY))
            ->addPermission((new PermissionTransfer())->setKey('SomeOtherPermissionPlugin'));
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('getRegisteredPermissions')->willReturn($permissionCollection);

        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getPermissionClient'])
            ->getMock();
        $controller->method('getPermissionClient')->willReturn($permissionClientMock);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkReplayPermissionRegistered');

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    public function testCheckReplayPermissionRegisteredReturnsFailedWithARemedyWhenTheKeyIsMissing(): void
    {
        // Arrange
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('getRegisteredPermissions')->willReturn(
            (new PermissionCollectionTransfer())->addPermission((new PermissionTransfer())->setKey('SomeOtherPermissionPlugin')),
        );

        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getPermissionClient'])
            ->getMock();
        $controller->method('getPermissionClient')->willReturn($permissionClientMock);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkReplayPermissionRegistered');

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('PermissionDependencyProvider.php', $check['remedy']);
    }

    public function testCheckSearchRankingSpecificityIntegrationPassesTriviallyWhenSearchRankingIsNotInstalled(): void
    {
        // Arrange
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['isSearchRankingInstalled'])
            ->getMock();
        $controller->method('isSearchRankingInstalled')->willReturn(false);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkSearchRankingSpecificityIntegration');

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    public function testCheckSearchRankingSpecificityIntegrationPassesWhenBothTheProviderPluginAndTheWeightingFlagAreOn(): void
    {
        // Arrange
        $controller = $this->buildControllerMockForSpecificityIntegration(true, true);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkSearchRankingSpecificityIntegration');

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    public function testCheckSearchRankingSpecificityIntegrationFailsWithARemedyWhenTheProviderPluginIsNotRegistered(): void
    {
        // Arrange
        $controller = $this->buildControllerMockForSpecificityIntegration(false, true);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkSearchRankingSpecificityIntegration');

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('SearchFeedbackDependencyProvider.php', $check['remedy']);
    }

    public function testCheckSearchRankingSpecificityIntegrationFailsWithARemedyWhenSpecificityWeightingIsOff(): void
    {
        // Arrange
        $controller = $this->buildControllerMockForSpecificityIntegration(true, false);

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkSearchRankingSpecificityIntegration');

        // Assert
        $this->assertFalse($check['passed']);
        $this->assertStringContainsString('isSpecificityWeightingEnabled', $check['remedy']);
    }

    public function testCheckSearchResultsTemplateMappingReturnsPassedWhenTheMappingIsPresent(): void
    {
        // Arrange
        $templateFilePath = $this->createTemplateFixtureFile("{% define data = {\n    searchFeedbackSnapshot: _view.searchFeedbackSnapshot | default,\n} %}");
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getSearchResultsTemplateFilePath'])
            ->getMock();
        $controller->method('getSearchResultsTemplateFilePath')->willReturn($templateFilePath);

        try {
            // Act
            $check = $this->invokeProtectedMethodOn($controller, 'checkSearchResultsTemplateMapping');

            // Assert
            $this->assertTrue($check['passed']);
            $this->assertNull($check['remedy']);
        } finally {
            unlink($templateFilePath);
        }
    }

    /**
     * The exact real-world shape confirmed live: the template maps OTHER packages' keys (search-debug's
     * `searchDebugTokens`) just fine, but never adds this package's own `searchFeedbackSnapshot` — a
     * missing mapping never errors, it just leaves `data.searchFeedbackSnapshot` undefined.
     */
    public function testCheckSearchResultsTemplateMappingReturnsFailedWithARemedyWhenTheMappingIsMissing(): void
    {
        // Arrange
        $templateFilePath = $this->createTemplateFixtureFile("{% define data = {\n    searchDebugTokens: _view.searchDebug.tokens | default([]),\n} %}");
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getSearchResultsTemplateFilePath'])
            ->getMock();
        $controller->method('getSearchResultsTemplateFilePath')->willReturn($templateFilePath);

        try {
            // Act
            $check = $this->invokeProtectedMethodOn($controller, 'checkSearchResultsTemplateMapping');

            // Assert
            $this->assertFalse($check['passed']);
            $this->assertStringContainsString('searchFeedbackSnapshot', $check['remedy']);
            $this->assertStringContainsString('search.twig', $check['remedy']);
        } finally {
            unlink($templateFilePath);
        }
    }

    /**
     * A project that doesn't use SprykerShop's CatalogPage search template at all (a fully custom SRP) has
     * no file to check — indistinguishable from "forgot the mapping" from outside, so this errs toward not
     * crying wolf rather than failing on a state it cannot actually diagnose.
     */
    public function testCheckSearchResultsTemplateMappingReturnsPassedWhenTheTemplateFileDoesNotExist(): void
    {
        // Arrange
        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['getSearchResultsTemplateFilePath'])
            ->getMock();
        $controller->method('getSearchResultsTemplateFilePath')->willReturn('/does/not/exist-' . uniqid() . '.twig');

        // Act
        $check = $this->invokeProtectedMethodOn($controller, 'checkSearchResultsTemplateMapping');

        // Assert
        $this->assertTrue($check['passed']);
        $this->assertNull($check['remedy']);
    }

    protected function createTemplateFixtureFile(string $contents): string
    {
        $templateFilePath = tempnam(sys_get_temp_dir(), 'search-results-template-fixture-');

        file_put_contents($templateFilePath, $contents);

        return $templateFilePath;
    }

    protected function buildControllerMockForSpecificityIntegration(bool $hasProviderPlugin, bool $isSpecificityWeightingEnabled): CheckInstallationController
    {
        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('hasTermVectorSnapshotProviderPlugin')->willReturn($hasProviderPlugin);

        // Stand-in for search-ranking's own Client, which this package cannot type-hint directly (see
        // getSearchRankingClient()'s own docblock) — a real mock needs a real class/interface to mock
        // against, so a tiny anonymous class fills in instead.
        $searchRankingClientMock = new class ($isSpecificityWeightingEnabled) {
            public function __construct(protected bool $isSpecificityWeightingEnabled)
            {
            }

            public function isSpecificityWeightingEnabled(): bool
            {
                return $this->isSpecificityWeightingEnabled;
            }
        };

        $controller = $this->getMockBuilder(CheckInstallationController::class)
            ->onlyMethods(['isSearchRankingInstalled', 'getSearchFeedbackClient', 'getSearchRankingClient'])
            ->getMock();
        $controller->method('isSearchRankingInstalled')->willReturn(true);
        $controller->method('getSearchFeedbackClient')->willReturn($searchFeedbackClientMock);
        $controller->method('getSearchRankingClient')->willReturn($searchRankingClientMock);

        return $controller;
    }

    /**
     * Same purpose as {@see invokeProtectedMethod()}, but invokes on an already-built controller (a mock
     * with specific getters stubbed) instead of always constructing a bare `new CheckInstallationController()`
     * — needed for the checks added above, which reach `Locator::getInstance()` through small overridable
     * getter methods rather than through `getApplication()->get($id)`.
     *
     * @param object $controller
     * @param string $methodName
     *
     * @return mixed
     */
    protected function invokeProtectedMethodOn(object $controller, string $methodName)
    {
        $reflectionMethod = new ReflectionMethod(CheckInstallationController::class, $methodName);

        return $reflectionMethod->invoke($controller);
    }

    public function testIsListenerBoundReturnsTrueWhenThePluginRegisteredAListenerForTheEvent(): void
    {
        // Arrange
        $eventDispatcher = new EventDispatcher();
        $plugin = new SearchFeedbackReplayContextEventDispatcherPlugin();
        $plugin->extend($eventDispatcher, $this->createMock(ContainerInterface::class));

        // Act
        $isBound = $this->invokeIsListenerBound($eventDispatcher, KernelEvents::REQUEST, SearchFeedbackReplayContextEventDispatcherPlugin::class);

        // Assert
        $this->assertTrue($isBound);
    }

    public function testIsListenerBoundReturnsFalseWhenNoListenerIsRegisteredForTheEvent(): void
    {
        // Arrange
        $eventDispatcher = new EventDispatcher();

        // Act
        $isBound = $this->invokeIsListenerBound($eventDispatcher, KernelEvents::REQUEST, SearchFeedbackReplayContextEventDispatcherPlugin::class);

        // Assert
        $this->assertFalse($isBound);
    }

    /**
     * A listener IS registered for the event, but bound to an unrelated object — confirms the check
     * identifies the specific plugin by its closure's bound `$this`, not merely "something listens".
     */
    public function testIsListenerBoundReturnsFalseWhenTheRegisteredListenerBelongsToADifferentClass(): void
    {
        // Arrange
        $eventDispatcher = new EventDispatcher();
        $unrelatedListenerOwner = new class {
        };
        $listener = Closure::bind(function (): void {
        }, $unrelatedListenerOwner);
        $eventDispatcher->addListener(KernelEvents::REQUEST, $listener);

        // Act
        $isBound = $this->invokeIsListenerBound($eventDispatcher, KernelEvents::REQUEST, SearchFeedbackReplayContextEventDispatcherPlugin::class);

        // Assert
        $this->assertFalse($isBound);
    }

    /**
     * @param \Spryker\Shared\EventDispatcher\EventDispatcher $eventDispatcher
     * @param string $eventName
     * @param class-string $listenerClassName
     */
    protected function invokeIsListenerBound(EventDispatcher $eventDispatcher, string $eventName, string $listenerClassName): bool
    {
        $reflectionMethod = new ReflectionMethod(CheckInstallationController::class, 'isListenerBound');

        return $reflectionMethod->invoke(new CheckInstallationController(), $eventDispatcher, $eventName, $listenerClassName);
    }

    /**
     * @param string $methodName
     * @param array<mixed> $args
     * @param \Spryker\Service\Container\ContainerInterface|null $container
     *
     * @return mixed
     */
    protected function invokeProtectedMethod(string $methodName, array $args, ?ContainerInterface $container = null)
    {
        $controller = new CheckInstallationController();

        if ($container !== null) {
            $controller->setApplication($container);
        }

        $reflectionMethod = new ReflectionMethod(CheckInstallationController::class, $methodName);

        return $reflectionMethod->invoke($controller, ...$args);
    }

    /**
     * A minimal `ContainerInterface` fixture answering exactly the 3 service ids `AbstractController`'s
     * `getTwig()`/`getApplication()->get('dispatcher')`/`getRouter()` ask for — every other
     * `ContainerInterface` method is unused by this controller and stubbed as a no-op.
     *
     * @param \Twig\Environment|null $twig
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $dispatcher
     * @param \Symfony\Cmf\Component\Routing\ChainRouterInterface|null $router
     */
    protected function createContainer(
        ?Environment $twig = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?ChainRouterInterface $router = null,
    ): ContainerInterface {
        return new class ($twig, $dispatcher, $router) implements ContainerInterface {
            public function __construct(
                protected ?Environment $twig,
                protected ?EventDispatcherInterface $dispatcher,
                protected ?ChainRouterInterface $router,
            ) {
            }

            /**
             * @param string $id
             *
             * @return mixed
             */
            public function get(string $id)
            {
                return match ($id) {
                    'twig' => $this->twig,
                    'dispatcher' => $this->dispatcher,
                    'routers' => $this->router,
                    default => null,
                };
            }

            public function has(string $id): bool
            {
                return $this->get($id) !== null;
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
             *
             * @param mixed $service
             */
            public function set(string $id, $service): void
            {
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
             *
             * @param mixed $service
             */
            public function setGlobal(string $id, $service): void
            {
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
             *
             * @param array<string, mixed> $configuration
             */
            public function configure(string $id, array $configuration): void
            {
            }

            /**
             * $service is deliberately untyped, matching `ContainerInterface::extend()`'s own untyped
             * parameter — narrowing it to `Closure` here would violate parameter contravariance against
             * the interface. The return type has no such constraint (covariant), so it stays native.
             *
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
             *
             * @param mixed $service
             */
            public function extend(string $id, $service): Closure
            {
                return $service;
            }

            /**
             * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
             */
            public function remove(string $id): void
            {
            }

            /**
             * @param \Closure|object $service
             *
             * @return \Closure|object
             */
            public function protect($service)
            {
                return $service;
            }

            /**
             * @param \Closure|object $service
             *
             * @return \Closure|object
             */
            public function factory($service)
            {
                return $service;
            }
        };
    }
}
