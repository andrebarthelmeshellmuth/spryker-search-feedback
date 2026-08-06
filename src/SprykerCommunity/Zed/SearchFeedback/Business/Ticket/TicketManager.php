<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Business\Ticket;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use InvalidArgumentException;
use OutOfBoundsException;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManagerInterface;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepositoryInterface;

class TicketManager implements TicketManagerInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManagerInterface $entityManager
     * @param \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepositoryInterface $repository
     */
    public function __construct(
        protected SearchFeedbackEntityManagerInterface $entityManager,
        protected SearchFeedbackRepositoryInterface $repository,
    ) {
    }

    /**
     * The permission re-check happens one layer up, in
     * {@see \SprykerCommunity\Zed\SearchFeedback\Communication\Controller\GatewayController} — this method
     * assumes the caller is already authorized and only validates that a body was actually submitted.
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer
    {
        if (trim($requestTransfer->getBodyOrFail()) === '') {
            return (new SearchFeedbackTicketResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage('A ticket needs a message body.');
        }

        if (!in_array($requestTransfer->getTopic(), (new SearchFeedbackConfig())->getTopics(), true)) {
            return (new SearchFeedbackTicketResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage('Unknown ticket topic.');
        }

        $ticketTransfer = $this->entityManager->createTicket($requestTransfer);

        return (new SearchFeedbackTicketResponseTransfer())
            ->setIsSuccess(true)
            ->setTicket($ticketTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer
     *
     * @throws \InvalidArgumentException The reply body is blank.
     * @throws \OutOfBoundsException The referenced ticket does not exist.
     */
    public function replyToTicket(SearchFeedbackTicketMessageRequestTransfer $messageRequestTransfer): SearchFeedbackTicketTransfer
    {
        $body = $messageRequestTransfer->getBodyOrFail();

        if (trim($body) === '') {
            throw new InvalidArgumentException('A reply needs a message body.');
        }

        $idSearchFeedbackTicket = $messageRequestTransfer->getIdSearchFeedbackTicketOrFail();

        if ($this->repository->findTicketById($idSearchFeedbackTicket) === null) {
            throw new OutOfBoundsException(sprintf('Ticket with id %d does not exist.', $idSearchFeedbackTicket));
        }

        $this->entityManager->addMessage(
            $idSearchFeedbackTicket,
            $body,
            SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER,
            $messageRequestTransfer->getIdUserOrFail(),
            null,
        );

        /** @var \Generated\Shared\Transfer\SearchFeedbackTicketTransfer $ticketTransfer */
        $ticketTransfer = $this->repository->findTicketById($idSearchFeedbackTicket);

        return $ticketTransfer;
    }

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     *
     * @throws \InvalidArgumentException The given status is not one of SearchFeedbackConfig::STATUS_*.
     * @throws \OutOfBoundsException The referenced ticket does not exist.
     */
    public function changeTicketStatus(int $idSearchFeedbackTicket, string $status): SearchFeedbackTicketTransfer
    {
        if (!in_array($status, (new SearchFeedbackConfig())->getStatuses(), true)) {
            throw new InvalidArgumentException(sprintf('Unknown ticket status "%s".', $status));
        }

        if ($this->repository->findTicketById($idSearchFeedbackTicket) === null) {
            throw new OutOfBoundsException(sprintf('Ticket with id %d does not exist.', $idSearchFeedbackTicket));
        }

        $this->entityManager->changeStatus($idSearchFeedbackTicket, $status);

        /** @var \Generated\Shared\Transfer\SearchFeedbackTicketTransfer $ticketTransfer */
        $ticketTransfer = $this->repository->findTicketById($idSearchFeedbackTicket);

        return $ticketTransfer;
    }

    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer
    {
        return $this->repository->getTicketCollection();
    }

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer
    {
        return $this->repository->findTicketById($idSearchFeedbackTicket);
    }
}
