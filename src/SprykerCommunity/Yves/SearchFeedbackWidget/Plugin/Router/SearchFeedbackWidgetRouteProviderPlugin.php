<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router;

use Spryker\Shared\Config\Config;
use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConstants;

class SearchFeedbackWidgetRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    /**
     * @var string
     */
    public const ROUTE_NAME_SUBMIT_TICKET = 'search-feedback-widget/submit-ticket';

    /**
     * @var string
     */
    public const ROUTE_NAME_CHECK_INSTALLATION = 'search-feedback-widget/check-installation';

    /**
     * @param \Spryker\Yves\Router\Route\RouteCollection $routeCollection
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/search-feedback-widget/submit-ticket', 'SearchFeedbackWidget', 'SubmitTicket', 'submitAction')
            ->setMethods(['POST']);
        $routeCollection->add(static::ROUTE_NAME_SUBMIT_TICKET, $route);

        $this->addCheckInstallationRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * Only registered when {@see SearchFeedbackConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED} allows it
     * (default: no) — a project opts in via its development-tier config, so unless that flag is explicitly
     * set, this route never exists and the URL 404s exactly like any nonexistent path, rather than
     * existing-but-denied. See that constant for why a runtime permission check alone would not be enough.
     *
     * @param \Spryker\Yves\Router\Route\RouteCollection $routeCollection
     */
    protected function addCheckInstallationRoute(RouteCollection $routeCollection): void
    {
        if (!Config::get(SearchFeedbackConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED, false)) {
            return;
        }

        $checkInstallationRoute = $this->buildRoute('/search-feedback-widget/check-installation', 'SearchFeedbackWidget', 'CheckInstallation', 'indexAction');
        $routeCollection->add(static::ROUTE_NAME_CHECK_INSTALLATION, $checkInstallationRoute);
    }
}
