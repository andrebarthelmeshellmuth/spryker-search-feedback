<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketSrpSnapshot;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionHandlerInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * @method \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackPersistenceFactory getFactory()
 */
class SearchFeedbackEntityManager extends AbstractEntityManager implements SearchFeedbackEntityManagerInterface
{
    use TransactionTrait;

    /**
     * {@inheritDoc}
     */
    public function getTransactionHandler(): TransactionHandlerInterface
    {
        return $this->createTransactionHandlerFactory()->createHandler();
    }

    /**
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function createTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketTransfer
    {
        return $this->getTransactionHandler()->handleTransaction(function () use ($requestTransfer): SearchFeedbackTicketTransfer {
            $ticketEntity = new SpySearchFeedbackTicket();
            $ticketEntity->setTopic($requestTransfer->getTopicOrFail());
            $ticketEntity->setSearchTerm($requestTransfer->getSearchTermOrFail());
            $ticketEntity->setFilters(json_encode($requestTransfer->getFilters()) ?: null);
            $ticketEntity->setPageNumber($requestTransfer->getPageNumber() ?: 1);
            $ticketEntity->setSkuList(json_encode($requestTransfer->getSkuList()) ?: null);
            $ticketEntity->setStatus(SearchFeedbackConfig::STATUS_OPEN);
            $ticketEntity->setStoreName($requestTransfer->getStoreNameOrFail());
            $ticketEntity->setLocaleName($requestTransfer->getLocaleNameOrFail());
            $ticketEntity->setCustomerReference($requestTransfer->getCustomerReferenceOrFail());
            $ticketEntity->save();

            $messageEntity = new SpySearchFeedbackTicketMessage();
            $messageEntity->setFkSearchFeedbackTicket($ticketEntity->getIdSearchFeedbackTicket());
            $messageEntity->setBody($requestTransfer->getBodyOrFail());
            $messageEntity->setAuthorType(SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER);
            $messageEntity->setCustomerReference($requestTransfer->getCustomerReferenceOrFail());
            $messageEntity->save();

            $snapshotTransfer = $requestTransfer->getSnapshot();
            $hasSnapshot = $snapshotTransfer !== null;

            if ($snapshotTransfer !== null) {
                $snapshotEntity = new SpySearchFeedbackTicketSrpSnapshot();
                $snapshotEntity->setFkSearchFeedbackTicket($ticketEntity->getIdSearchFeedbackTicket());
                $snapshotEntity->setRawResponse($snapshotTransfer->getRawResponseOrFail());
                $snapshotEntity->setQueryDsl($snapshotTransfer->getQueryDslOrFail());
                $snapshotEntity->setRequestParameters($snapshotTransfer->getRequestParameters());
                $snapshotEntity->setHasTermVectorSnapshot($snapshotTransfer->getHasTermVectorSnapshot() ?? false);
                $snapshotEntity->setTermVectorSnapshot($snapshotTransfer->getTermVectorSnapshot());
                $snapshotEntity->save();
            }

            return $this->getFactory()
                ->createSearchFeedbackMapper()
                ->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer(), [$messageEntity], $hasSnapshot);
        });
    }

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $body
     * @param string $authorType
     * @param int|null $idUser
     * @param string|null $customerReference
     */
    public function addMessage(
        int $idSearchFeedbackTicket,
        string $body,
        string $authorType,
        ?int $idUser,
        ?string $customerReference,
    ): void {
        $messageEntity = new SpySearchFeedbackTicketMessage();
        $messageEntity->setFkSearchFeedbackTicket($idSearchFeedbackTicket);
        $messageEntity->setBody($body);
        $messageEntity->setAuthorType($authorType);
        $messageEntity->setFkUser($idUser);
        $messageEntity->setCustomerReference($customerReference);
        $messageEntity->save();
    }

    /**
     * @param int $idSearchFeedbackTicket
     * @param string $status
     */
    public function changeStatus(int $idSearchFeedbackTicket, string $status): void
    {
        $ticketEntity = $this->getFactory()
            ->createSearchFeedbackTicketQuery()
            ->filterByIdSearchFeedbackTicket($idSearchFeedbackTicket)
            ->findOne();

        if ($ticketEntity === null) {
            return;
        }

        $ticketEntity->setStatus($status);
        $ticketEntity->save();
    }
}
