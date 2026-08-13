<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidget\Controller;

use Closure;
use Codeception\Test\Unit;
use ReflectionMethod;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Yves\Kernel\View\View;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router\SearchFeedbackWidgetRouteProviderPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Twig\SearchFeedbackWidgetTwigPlugin;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * `indexAction()` and its container-touching helpers (`checkTwigFunctions()`, `checkRoutes()`,
 * `isTwigFunctionCallable()`, `isRouteRegistered()`) are exercised here against a minimal hand-built
 * `ContainerInterface` fixture (a real `Twig\Environment`, a mocked router) rather than a full
 * Silex/Symfony application boot — `AbstractController` only ever reaches its container through
 * `getApplication()->get($id)`, so a fixture answering exactly the 2 service ids this controller asks for
 * (`twig`, `routers`) is a faithful stand-in without needing the real app. `can()`/`runChecks()`/`view()`
 * are partial-mocked where `indexAction()`'s own branching (not a sub-check's internals) is what's under
 * test. Mirrors the sibling spryker-community/search-debug package's identical test for its own
 * CheckInstallationController.
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

    public function testRunChecksReturnsBothChecksInOrder(): void
    {
        // Arrange — a container that passes every individual check, so the only thing under test here is
        // that both run and land in the right order.
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET, fn () => true));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_TICKET_CSRF_TOKEN, fn () => ''));
        $twig->addFunction(new TwigFunction(SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_GET_TOPICS, fn () => []));
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willReturn('/some/url');

        // Act
        $checks = $this->invokeProtectedMethod('runChecks', [], $this->createContainer($twig, $router));

        // Assert
        $this->assertCount(2, $checks);
        $this->assertTrue($checks[0]['passed']);
        $this->assertTrue($checks[1]['passed']);
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
        $check = $this->invokeProtectedMethod('checkRoutes', [], $this->createContainer(null, $router));

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
        $check = $this->invokeProtectedMethod('checkRoutes', [], $this->createContainer(null, $router));

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
        $isRegistered = $this->invokeProtectedMethod('isRouteRegistered', [SearchFeedbackWidgetRouteProviderPlugin::ROUTE_NAME_SUBMIT_TICKET], $this->createContainer(null, $router));

        // Assert
        $this->assertTrue($isRegistered);
    }

    public function testIsRouteRegisteredReturnsFalseWhenTheRouterThrowsRouteNotFoundException(): void
    {
        // Arrange
        $router = $this->createMock(ChainRouterInterface::class);
        $router->method('generate')->willThrowException(new RouteNotFoundException());

        // Act
        $isRegistered = $this->invokeProtectedMethod('isRouteRegistered', ['some-route'], $this->createContainer(null, $router));

        // Assert
        $this->assertFalse($isRegistered);
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
     * A minimal `ContainerInterface` fixture answering exactly the 2 service ids `AbstractController`'s
     * `getTwig()`/`getRouter()` ask for — every other `ContainerInterface` method is unused by this
     * controller and stubbed as a no-op.
     *
     * @param \Twig\Environment|null $twig
     * @param \Symfony\Cmf\Component\Routing\ChainRouterInterface|null $router
     */
    protected function createContainer(?Environment $twig = null, ?ChainRouterInterface $router = null): ContainerInterface
    {
        return new class ($twig, $router) implements ContainerInterface {
            public function __construct(
                protected ?Environment $twig,
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
