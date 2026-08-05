<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @method \SprykerCommunity\Zed\SearchFeedbackGui\Communication\SearchFeedbackGuiCommunicationFactory getFactory()
 */
class IndexController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    public function indexAction(): array
    {
        return $this->viewResponse([
            'ticketTable' => $this->getFactory()->createTicketTable()->render(),
        ]);
    }

    /**
     * AJAX data source the rendered table's own JS polls against (DataTables-style server-side
     * processing) — same convention search-ranking/search-ranking-optimizer's own Index/Table pairings use.
     */
    public function tableAction(): JsonResponse
    {
        return $this->jsonResponse(
            $this->getFactory()->createTicketTable()->fetchData(),
        );
    }
}
