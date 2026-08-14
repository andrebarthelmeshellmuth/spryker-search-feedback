<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Controller;

use Closure;
use ReflectionFunction;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\Locator;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface;
use SprykerCommunity\Client\SearchRanking\SearchRankingClientInterface;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\EventDispatcher\SearchFeedbackReplayContextEventDispatcherPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router\SearchFeedbackWidgetRouteProviderPlugin;
use SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Twig\SearchFeedbackWidgetTwigPlugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
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
 * Of README step 11's five frozen-replay sub-registrations, this page plus the Zed console command
 * together verify four directly. The fifth, `SearchFeedbackSnapshotResultFormatterPlugin` in the Client
 * `CatalogDependencyProvider::CATALOG_SEARCH_RESULT_FORMATTER_PLUGINS` list, cannot be checked directly —
 * it lives inside `spryker/catalog`'s own internal formatter-plugin collection, not this package's own
 * classes, so reflecting into it would be reaching into core internals. {@see checkSearchResultsTemplateMapping()}
 * catches the actual silent-failure SYMPTOM that registration gap produces instead (confirmed live): even
 * with the formatter correctly registered and capturing every search, the search-results template can
 * still fail to forward the captured token into `data`, which looks identical from the outside. Confirm
 * the formatter registration itself by hand: submit a ticket, then check its Zed detail page's "View SRP"
 * replays the frozen snapshot instead of silently re-running a live search.
 *
 * @method \SprykerCommunity\Yves\SearchFeedbackWidget\SearchFeedbackWidgetFactory getFactory()
 */
class CheckInstallationController extends AbstractController
{
    use PermissionAwareTrait;

    /**
     * @uses \Spryker\Yves\EventDispatcher\Plugin\Application\EventDispatcherApplicationPlugin::SERVICE_DISPATCHER
     *
     * @var string
     */
    protected const SERVICE_DISPATCHER = 'dispatcher';

    /**
     * SprykerShop's own CatalogPage bundle ships this exact file at
     * `Theme/default/views/search/search.twig`; a project overriding it follows the same standard Spryker
     * theme-override path. See {@see checkSearchResultsTemplateMapping()}. Relative to `APPLICATION_ROOT_DIR`.
     *
     * @var string
     */
    protected const SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH = '/src/Pyz/Yves/CatalogPage/Theme/default/views/search/search.twig';

    /**
     * The exact `{% define data %}` mapping the ticket-form include (README step 6) needs present in the
     * project's search-results template. See {@see checkSearchResultsTemplateMapping()}.
     *
     * @var string
     */
    protected const SEARCH_RESULTS_TEMPLATE_EXPECTED_MAPPING = 'searchFeedbackSnapshot';

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
            $this->checkFrozenReplayEventListener(),
            $this->checkReplayPermissionRegistered(),
            $this->checkSearchRankingSpecificityIntegration(),
            $this->checkSearchResultsTemplateMapping(),
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

    /**
     * Frozen replay (README step 11) is optional, so a failure here is worded as "not wired up", not as a
     * defect — but this is the ONE registration from that step this package's Zed console command
     * ({@see \SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole})
     * cannot reach either, since it never bootstraps the Yves DI container. Miss it and a replay link
     * (`?srpReplayTicket=<id>`) is never gated by {@see \SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin}
     * at the Yves layer — {@see SearchFeedbackReplayContextEventDispatcherPlugin}'s own docblock notes the
     * Zed gateway still re-checks authorization independently, so this is a UX gap (no friendly
     * login-redirect / 403), not a security hole, but worth flagging the same way every other silent gap
     * in this checklist is.
     *
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkFrozenReplayEventListener(): array
    {
        $eventDispatcher = $this->getApplication()->get(static::SERVICE_DISPATCHER);
        $isRegistered = $this->isListenerBound($eventDispatcher, KernelEvents::REQUEST, SearchFeedbackReplayContextEventDispatcherPlugin::class);

        return [
            'label' => 'Frozen replay event listener (SearchFeedbackReplayContextEventDispatcherPlugin) is registered (optional, README step 11)',
            'passed' => $isRegistered,
            'remedy' => $isRegistered
                ? null
                : 'Register SearchFeedbackReplayContextEventDispatcherPlugin in src/Pyz/Yves/EventDispatcher/EventDispatcherDependencyProvider.php (see README step 11). Skip this if you intentionally do not use frozen replay.',
        ];
    }

    /**
     * Pure over an already-resolved dispatcher and event name — no framework bootstrap needed to test
     * this in isolation, unlike the container access in {@see checkFrozenReplayEventListener()}.
     *
     * A registered plugin's listener closure is created INSIDE `extend()`, an instance method on the
     * plugin — PHP auto-binds `$this` on any closure created inside a non-static method, so the closure's
     * bound object is the plugin instance itself (see `SearchFeedbackReplayContextEventDispatcherPlugin::extend()`).
     * Reflecting that binding is the only way to identify WHICH plugin registered a given listener, since
     * Symfony's `EventDispatcherInterface::getListeners()` returns plain callables with no origin info.
     * Same technique the sibling search-debug package's own check-installation controller already uses.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param string $eventName
     * @param class-string $listenerClassName
     */
    protected function isListenerBound(EventDispatcherInterface $eventDispatcher, string $eventName, string $listenerClassName): bool
    {
        foreach ($eventDispatcher->getListeners($eventName) as $listener) {
            if (!($listener instanceof Closure)) {
                continue;
            }

            $boundObject = (new ReflectionFunction($listener))->getClosureThis();

            if ($boundObject instanceof $listenerClassName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks {@see ViewSearchFeedbackTicketReplayPermissionPlugin}'s CLIENT-side registration via
     * `PermissionClientInterface::getRegisteredPermissions()` — a real, `@api` Spryker method that lists
     * every registered permission key, the same entry point `PermissionAwareTrait::can()` (used by this
     * controller and every other permission gate in this package) resolves through. Deliberately not
     * `$this->can(ViewSearchFeedbackTicketReplayPermissionPlugin::KEY)`: that returns `false` for both "not
     * registered" and "registered but this customer isn't granted it", so it can't tell a wiring gap from
     * a fixture gap — this check can. Only covers the Client half; the Zed-side registration (also
     * required, per README step 11) is a Zed-container concern this Yves page cannot reach either, same
     * blind spot the sibling Zed console command already documents for its own half of this step.
     *
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkReplayPermissionRegistered(): array
    {
        $isRegistered = false;

        foreach ($this->getPermissionClient()->getRegisteredPermissions()->getPermissions() as $permissionTransfer) {
            if ($permissionTransfer->getKey() === ViewSearchFeedbackTicketReplayPermissionPlugin::KEY) {
                $isRegistered = true;

                break;
            }
        }

        return [
            'label' => 'ViewSearchFeedbackTicketReplayPermissionPlugin is registered on the Client (optional, README step 11)',
            'passed' => $isRegistered,
            'remedy' => $isRegistered
                ? null
                : 'Register ViewSearchFeedbackTicketReplayPermissionPlugin in src/Pyz/Client/Permission/PermissionDependencyProvider.php AND src/Pyz/Zed/Permission/PermissionDependencyProvider.php (see README step 11; only the Client half is checkable from here). Skip this if you intentionally do not use frozen replay.',
        ];
    }

    /**
     * Covers the optional search-ranking sub-step of README step 11: registering
     * `SearchFeedbackTermVectorSnapshotProviderPlugin` only produces a non-null result once
     * search-ranking's own specificity weighting is turned on too (off by default there — see its
     * README's step 14c). When search-ranking isn't installed at all, this check is not applicable and
     * passes trivially — there is nothing to register or enable.
     *
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkSearchRankingSpecificityIntegration(): array
    {
        if (!$this->isSearchRankingInstalled()) {
            return [
                'label' => 'search-ranking specificity-weighting integration is wired up and enabled (optional, README step 11 — not applicable, spryker-community/search-ranking is not installed)',
                'passed' => true,
                'remedy' => null,
            ];
        }

        $hasProviderPlugin = $this->getSearchFeedbackClient()->hasTermVectorSnapshotProviderPlugin();
        $isSpecificityWeightingEnabled = $this->getSearchRankingClient()->isSpecificityWeightingEnabled();

        $remedy = null;

        if (!$hasProviderPlugin) {
            $remedy = 'Register SearchFeedbackTermVectorSnapshotProviderPlugin in src/Pyz/Client/SearchFeedback/SearchFeedbackDependencyProvider.php::getTermVectorSnapshotProviderPlugins() (see README step 11).';
        } elseif (!$isSpecificityWeightingEnabled) {
            $remedy = 'SearchFeedbackTermVectorSnapshotProviderPlugin is registered, but search-ranking\'s specificity weighting is off (its default) — override Pyz\Client\SearchRanking\SearchRankingConfig::isSpecificityWeightingEnabled() to return true, or every ticket snapshot\'s specificity result stays null (see search-ranking\'s README, step 14c).';
        }

        return [
            'label' => 'search-ranking specificity-weighting integration is wired up and enabled (optional, README step 11)',
            'passed' => $hasProviderPlugin && $isSpecificityWeightingEnabled,
            'remedy' => $remedy,
        ];
    }

    /**
     * `SearchFeedbackSnapshotResultFormatterPlugin` running (README step 11) is necessary but NOT
     * sufficient — the package's own class docblock already names that as the one registration this
     * whole check-installation pair can't reach (it lives inside `spryker/catalog`'s own internal
     * formatter-plugin collection, not this package's classes). This check instead catches the actual
     * silent-failure point confirmed live: SprykerShop's own search-results template
     * ({@see SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH}) explicitly whitelists which controller-returned
     * fields it forwards into `data` (`{% define data = {...} %}`, reading from `_view.*`) — the same
     * pattern this project already follows for search-debug's `searchDebugTokens` and search-ranking's
     * `randomImpactIsActive`. Miss adding this package's own `searchFeedbackSnapshot` mapping and the
     * formatter plugin still runs and captures correctly every time (a session entry always appears), but
     * `data.searchFeedbackSnapshot.token` is silently empty where the ticket-form include (README step 6)
     * reads it — the hidden `snapshotToken` field never renders, every ticket saves with zero snapshot
     * rows, and nothing anywhere errors.
     *
     * A WARNING, not a failure, for two independent reasons: frozen replay (step 11) is optional, and a
     * project that doesn't use SprykerShop's `CatalogPage` search template at all (a fully custom SRP) has
     * no file to check here — that state is indistinguishable from "forgot the mapping" from outside, so
     * this check cannot tell them apart and errs toward not crying wolf.
     *
     * @return array{label: string, passed: bool, remedy: string|null}
     */
    protected function checkSearchResultsTemplateMapping(): array
    {
        $templateFilePath = $this->getSearchResultsTemplateFilePath();

        if (!is_readable($templateFilePath)) {
            return [
                'label' => sprintf('%s maps searchFeedbackSnapshot into data (optional, README step 11)', static::SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH),
                'passed' => true,
                'remedy' => null,
            ];
        }

        $isMapped = str_contains((string)file_get_contents($templateFilePath), static::SEARCH_RESULTS_TEMPLATE_EXPECTED_MAPPING);

        return [
            'label' => sprintf('%s maps searchFeedbackSnapshot into data (optional, README step 11)', static::SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH),
            'passed' => $isMapped,
            'remedy' => $isMapped
                ? null
                : sprintf(
                    'Add "searchFeedbackSnapshot: _view.searchFeedbackSnapshot | default," to the {%% define data = {...} %%} block in %s (see README step 11). Without it the ticket form\'s hidden snapshotToken field silently stays empty and every ticket saves with no frozen-replay snapshot — no error anywhere. Skip this if you intentionally do not use frozen replay.',
                    static::SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH,
                ),
        ];
    }

    /**
     * Isolated as its own method so a test can point it at a fixture file instead of this host shop's real
     * template — same seam-for-testability reasoning as
     * {@see \SprykerCommunity\Zed\SearchFeedback\Communication\Console\SearchFeedbackCheckInstallationConsole::getSearchElasticsearchFactoryOverrideFilePath()}.
     */
    protected function getSearchResultsTemplateFilePath(): string
    {
        return APPLICATION_ROOT_DIR . static::SEARCH_RESULTS_TEMPLATE_RELATIVE_PATH;
    }

    /**
     * Isolated as its own method, rather than an inline `interface_exists()` call, so a test can override
     * it directly instead of needing a real-or-absent `SprykerCommunity\Client\SearchRanking\SearchRankingClientInterface`
     * on the autoloader — same isolation reasoning as {@see getSearchElasticsearchFactoryOverrideFilePath()}
     * in the sibling Zed console command.
     */
    protected function isSearchRankingInstalled(): bool
    {
        return interface_exists(SearchRankingClientInterface::class);
    }

    /**
     * Thin wrapper around the global `Locator` singleton so a test can substitute a mock instead of
     * needing a real Permission Client bootstrap — same reasoning `PermissionAwareTrait::can()` itself
     * doesn't offer (it calls `Locator::getInstance()` inline), which is exactly why this controller's own
     * tests mock `can()` wholesale rather than exercising it for real.
     */
    protected function getPermissionClient(): PermissionClientInterface
    {
        return Locator::getInstance()->permission()->client();
    }

    protected function getSearchFeedbackClient(): SearchFeedbackClientInterface
    {
        return Locator::getInstance()->searchFeedback()->client();
    }

    /**
     * Only ever called after {@see isSearchRankingInstalled()} confirms the interface exists, so the
     * return type can't be declared as the sibling package's own class without making this file
     * (and its whole namespace) fail to autoload when that optional package is absent.
     */
    protected function getSearchRankingClient(): object
    {
        return Locator::getInstance()->searchRanking()->client();
    }
}
