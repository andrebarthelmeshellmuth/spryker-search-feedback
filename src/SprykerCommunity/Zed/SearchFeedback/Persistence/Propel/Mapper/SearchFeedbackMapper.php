<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\SearchFeedbackTicketMessageTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketSrpSnapshot;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

class SearchFeedbackMapper
{
    /**
     * Messages are passed in explicitly (a separate, explicit query in the Repository) rather than pulled
     * off a Propel relation-collection getter — this package has exactly one place that ever needs a
     * ticket's messages, so an explicit, unambiguous query there is clearer than relying on a generated
     * collection-accessor name. hasSnapshot is likewise a caller-supplied fact (a cheap existence check in
     * the Repository) rather than a relation traversal — the full snapshot payload itself is never loaded
     * here, only its presence; see mapSrpSnapshotEntityToTransfer() for the dedicated full-payload path
     * the replay gateway action uses instead.
     *
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket $ticketEntity
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketTransfer $ticketTransfer
     * @param array<\Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage> $messageEntities
     * @param bool $hasSnapshot
     */
    public function mapTicketEntityToTransfer(
        SpySearchFeedbackTicket $ticketEntity,
        SearchFeedbackTicketTransfer $ticketTransfer,
        array $messageEntities = [],
        bool $hasSnapshot = false,
    ): SearchFeedbackTicketTransfer {
        $ticketTransfer
            ->setIdSearchFeedbackTicket($ticketEntity->getIdSearchFeedbackTicket())
            ->setTopic($ticketEntity->getTopic())
            ->setSearchTerm($ticketEntity->getSearchTerm())
            ->setFilters($this->decodeJson($ticketEntity->getFilters()))
            ->setPageNumber($ticketEntity->getPageNumber())
            ->setSkuList($this->decodeJson($ticketEntity->getSkuList()))
            ->setStatus($ticketEntity->getStatus())
            ->setStoreName($ticketEntity->getStoreName())
            ->setLocaleName($ticketEntity->getLocaleName())
            ->setCustomerReference($ticketEntity->getCustomerReference())
            ->setCreatedAt($ticketEntity->getCreatedAt()?->format(DATE_ATOM))
            ->setUpdatedAt($ticketEntity->getUpdatedAt()?->format(DATE_ATOM))
            ->setHasSnapshot($hasSnapshot);

        foreach ($messageEntities as $messageEntity) {
            $ticketTransfer->addMessage($this->mapMessageEntityToTransfer($messageEntity, new SearchFeedbackTicketMessageTransfer()));
        }

        return $ticketTransfer;
    }

    /**
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketSrpSnapshot $snapshotEntity
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer $snapshotTransfer
     */
    public function mapSrpSnapshotEntityToTransfer(
        SpySearchFeedbackTicketSrpSnapshot $snapshotEntity,
        SearchFeedbackTicketSrpSnapshotTransfer $snapshotTransfer,
    ): SearchFeedbackTicketSrpSnapshotTransfer {
        return $snapshotTransfer
            ->setRawResponse($snapshotEntity->getRawResponse())
            ->setQueryDsl($snapshotEntity->getQueryDsl())
            ->setRequestParameters($snapshotEntity->getRequestParameters())
            ->setHasTermVectorSnapshot($snapshotEntity->getHasTermVectorSnapshot())
            ->setTermVectorSnapshot($snapshotEntity->getTermVectorSnapshot());
    }

    /**
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessage $messageEntity
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketMessageTransfer $messageTransfer
     */
    public function mapMessageEntityToTransfer(
        SpySearchFeedbackTicketMessage $messageEntity,
        SearchFeedbackTicketMessageTransfer $messageTransfer,
    ): SearchFeedbackTicketMessageTransfer {
        $authorLabel = $messageEntity->getAuthorType() === SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER
            ? ($messageEntity->getUser()?->getUsername() ?? '')
            : $messageEntity->getCustomerReference();

        return $messageTransfer
            ->setIdSearchFeedbackTicketMessage($messageEntity->getIdSearchFeedbackTicketMessage())
            ->setFkSearchFeedbackTicket($messageEntity->getFkSearchFeedbackTicket())
            ->setBody($messageEntity->getBody())
            ->setAuthorType($messageEntity->getAuthorType())
            ->setAuthorLabel($authorLabel)
            ->setCreatedAt($messageEntity->getCreatedAt()?->format(DATE_ATOM));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        return (array)json_decode($json, true);
    }
}
