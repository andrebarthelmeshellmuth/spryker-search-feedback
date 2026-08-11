<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerCommunity\Zed\SearchFeedbackGui\Communication\SearchFeedbackGuiCommunicationFactory getFactory()
 */
class IndexController extends AbstractController
{
    /**
     * @var string
     */
    protected const PARAM_STORE_NAME = 'storeName';

    /**
     * @var string
     */
    protected const PARAM_LOCALE_NAME = 'localeName';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array<string, mixed>
     */
    public function indexAction(Request $request): array
    {
        $storeName = $this->resolveStoreName($request);
        $localeName = $this->resolveLocaleName($request);

        return $this->viewResponse([
            'ticketTable' => $this->getFactory()->createTicketTable($storeName, $localeName)->render(),
            'stores' => $this->getFactory()->getAllStoreNames(),
            'locales' => $this->getFactory()->getAllLocaleNames(),
            'selectedStoreName' => $storeName,
            'selectedLocaleName' => $localeName,
        ]);
    }

    /**
     * AJAX data source the rendered table's own JS polls against (DataTables-style server-side
     * processing) — same convention search-ranking/search-ranking-optimizer's own Index/Table pairings use.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function tableAction(Request $request): JsonResponse
    {
        return $this->jsonResponse(
            $this->getFactory()
                ->createTicketTable($this->resolveStoreName($request), $this->resolveLocaleName($request))
                ->fetchData(),
        );
    }

    /**
     * Null (not a default scope) means "no filter" — this is a cross-scope support-ticket triage view,
     * so an unset/blank query param means "show every store", not "fall back to DE".
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    protected function resolveStoreName(Request $request): ?string
    {
        $storeName = (string)$request->query->get(static::PARAM_STORE_NAME, '');

        return $storeName === '' ? null : $storeName;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    protected function resolveLocaleName(Request $request): ?string
    {
        $localeName = (string)$request->query->get(static::PARAM_LOCALE_NAME, '');

        return $localeName === '' ? null : $localeName;
    }
}
