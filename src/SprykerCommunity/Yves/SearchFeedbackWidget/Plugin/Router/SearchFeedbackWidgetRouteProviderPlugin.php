<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

class SearchFeedbackWidgetRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    /**
     * @var string
     */
    public const ROUTE_NAME_SUBMIT_TICKET = 'search-feedback-widget/submit-ticket';

    /**
     * @param \Spryker\Yves\Router\Route\RouteCollection $routeCollection
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/search-feedback-widget/submit-ticket', 'SearchFeedbackWidget', 'SubmitTicket', 'submitAction')
            ->setMethods(['POST']);
        $routeCollection->add(static::ROUTE_NAME_SUBMIT_TICKET, $route);

        return $routeCollection;
    }
}
