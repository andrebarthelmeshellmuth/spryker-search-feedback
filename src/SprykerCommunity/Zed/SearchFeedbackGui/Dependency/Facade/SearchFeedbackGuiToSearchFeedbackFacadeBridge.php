<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade;

use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;

class SearchFeedbackGuiToSearchFeedbackFacadeBridge implements SearchFeedbackGuiToSearchFeedbackFacadeInterface
{
    /**
     * @var \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacadeInterface
     */
    protected $searchFeedbackFacade;

    /**
     * @param \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacadeInterface $searchFeedbackFacade
     */
    public function __construct($searchFeedbackFacade)
    {
        $this->searchFeedbackFacade = $searchFeedbackFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer
    {
        return $this->searchFeedbackFacade->replyToTicket($messageRequestTransfer);
    }

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer
    {
        return $this->searchFeedbackFacade->changeTicketStatus($idSearchFeedbackTicket, $status);
    }

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer
    {
        return $this->searchFeedbackFacade->findTicketById($idSearchFeedbackTicket);
    }
}
