<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Business;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackBusinessFactory getFactory()
 */
class SearchFeedbackFacade extends AbstractFacade implements SearchFeedbackFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer
    {
        return $this->getFactory()->createTicketManager()->submitTicket($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     *
     * @throws \InvalidArgumentException The reply body is blank.
     * @throws \OutOfBoundsException The referenced ticket does not exist.
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer
    {
        return $this->getFactory()->createTicketManager()->replyToTicket($messageRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idSearchFeedbackTicket
     * @param string $status
     *
     * @throws \InvalidArgumentException The given status is not one of SearchFeedbackConfig::STATUS_*.
     * @throws \OutOfBoundsException The referenced ticket does not exist.
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer
    {
        return $this->getFactory()->createTicketManager()->changeTicketStatus($idSearchFeedbackTicket, $status);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer
    {
        return $this->getFactory()->createTicketManager()->getTicketCollection();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer
    {
        return $this->getFactory()->createTicketManager()->findTicketById($idSearchFeedbackTicket);
    }
}
