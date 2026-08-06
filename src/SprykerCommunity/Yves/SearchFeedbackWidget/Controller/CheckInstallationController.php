<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router\SearchFeedbackWidgetRouteProviderPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Twig\SearchFeedbackWidgetTwigPlugin;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Error\SyntaxError;

/**
 * Diagnoses the Yves-side half of a search-feedback installation — the half
 * {@see \SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole}
 * explicitly cannot reach, because Zed never bootstraps the Yves DI container. Complementary to that
 * console command, not a replacement for it: this page does not re-check the core namespace, plugin
 * class loadability, or ticket-table reachability — run the console command for those.
 *
 * Reachable only when BOTH gates pass: the route itself only exists when
 * {@see \SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED}
 * allows it (defaults to `false` — a project opts in via its development-tier config, so the URL 404s
 * everywhere else regardless of permission — see that constant for why), AND the visiting customer holds
 * {@see SubmitSearchFeedbackTicketPermissionPlugin}. Missing the permission on an environment where the
 * route does exist renders a dedicated explanation with the exact remedy, rather than a bare 403 — a
 * customer lacking the permission is not a security incident here, it is almost always someone mid-setup
 * who has not granted it yet, so it does not warrant the exact same anonymous non-response an unflagged
 * environment gets. Same posture as the sibling
 * {@see \SprykerCommunity\Yves\SearchDebugWidget\Controller\CheckInstallationController}.
 *
 * @method \SprykerCommunity\Yves\SearchFeedbackWidget\SearchFeedbackWidgetFactory getFactory()
 */
class CheckInstallationController extends AbstractController
{
    use PermissionAwareTrait;

    /**
     * @return \Spryker\Yves\Kernel\View\View|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->can(SubmitSearchFeedbackTicketPermissionPlugin::KEY)) {
            return $this->renderView(
                '@SearchFeedbackWidget/views/check-installation/permission-denied.twig',
                [],
                new Response('', Response::HTTP_FORBIDDEN),
            );
        }

        return $this->view(
            [
                'checks' => $this->runChecks(),
            ],
            [],
            '@SearchFeedbackWidget/views/check-installation/check-installation.twig',
        );
    }

    /**
     * @return array<int, array{label: string, passed: bool, remedy: string|null}>
     */
    protected function runChecks(): array
    {
        return [
            $this->checkTwigFunctions(),
            $this->checkRoutes(),
        ];
    }

    /**
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkTwigFunctions(): array
    {
        $functionNames = [
            SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_CAN_SUBMIT_TICKET,
            SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_TICKET_CSRF_TOKEN,
            SearchFeedbackWidgetTwigPlugin::FUNCTION_NAME_GET_TOPICS,
        ];

        $missingFunctionNames = [];

        foreach ($functionNames as $functionName) {
            if ($this->isTwigFunctionCallable($functionName)) {
                continue;
            }

            $missingFunctionNames[] = $functionName;
        }

        return [
            'label' => 'Twig helper functions (canSubmitSearchFeedbackTicket, searchFeedbackTicketCsrfToken, getSearchFeedbackTicketTopics) are registered',
            'passed' => $missingFunctionNames === [],
            'remedy' => $missingFunctionNames === []
                ? null
                : sprintf(
                    'Register SearchFeedbackWidgetTwigPlugin in src/Pyz/Yves/Twig/TwigDependencyProvider.php (see README step 4). Missing: %s.',
                    implode(', ', $missingFunctionNames),
                ),
        ];
    }

    /**
     * Compiles a throwaway one-line template that calls the function, rather than inspecting
     * `Twig\Environment`'s function registry directly — that registry is only reachable through
     * `getFunction()`, which Twig marks `@internal`. `createTemplate()` is Twig's own documented,
     * non-internal way to ask "does this compile", and it already throws {@see SyntaxError} for an
     * unknown function at compile time (see its own `@throws` docblock), so no render is needed either.
     *
     * @param string $functionName
     */
    protected function isTwigFunctionCallable(string $functionName): bool
    {
        try {
            $this->getTwig()->createTemplate(sprintf('{{ %s() }}', $functionName));

            return true;
        } catch (SyntaxError) {
            return false;
        }
    }

    /**
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkRoutes(): array
    {
        $isRegistered = $this->isRouteRegistered(SearchFeedbackWidgetRouteProviderPlugin::ROUTE_NAME_SUBMIT_TICKET);

        return [
            'label' => 'The submit-ticket route is registered',
            'passed' => $isRegistered,
            'remedy' => $isRegistered
                ? null
                : 'Register SearchFeedbackWidgetRouteProviderPlugin in src/Pyz/Yves/Router/RouterDependencyProvider.php (see README step 4).',
        ];
    }

    /**
     * @param string $routeName
     */
    protected function isRouteRegistered(string $routeName): bool
    {
        try {
            $this->getRouter()->generate($routeName);

            return true;
        } catch (RouteNotFoundException) {
            return false;
        }
    }
}
