<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Orm\Zed\SearchFeedback\Persistence\Map\SpySearchFeedbackTicketTableMap;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackPersistenceFactory getFactory()
 */
class SearchFeedbackRepository extends AbstractRepository implements SearchFeedbackRepositoryInterface
{
    public function getTicketCollection(): SearchFeedbackTicketCollectionTransfer
    {
        $ticketEntities = $this->getFactory()
            ->createSearchFeedbackTicketQuery()
            ->orderBy(SpySearchFeedbackTicketTableMap::COL_CREATED_AT, 'DESC')
            ->find();

        $collectionTransfer = new SearchFeedbackTicketCollectionTransfer();
        $mapper = $this->getFactory()->createSearchFeedbackMapper();

        foreach ($ticketEntities as $ticketEntity) {
            $collectionTransfer->addTicket($mapper->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer()));
        }

        return $collectionTransfer;
    }

    /**
     * @param int $idSearchFeedbackTicket
     */
    public function findTicketById(int $idSearchFeedbackTicket): ?SearchFeedbackTicketTransfer
    {
        $ticketEntity = $this->getFactory()
            ->createSearchFeedbackTicketQuery()
            ->filterByIdSearchFeedbackTicket($idSearchFeedbackTicket)
            ->findOne();

        if ($ticketEntity === null) {
            return null;
        }

        $messageEntities = $this->getFactory()
            ->createSearchFeedbackTicketMessageQuery()
            ->filterByFkSearchFeedbackTicket($idSearchFeedbackTicket)
            ->orderByCreatedAt()
            ->find();

        $hasSnapshot = $this->getFactory()
            ->createSearchFeedbackTicketSrpSnapshotQuery()
            ->filterByFkSearchFeedbackTicket($idSearchFeedbackTicket)
            ->exists();

        return $this->getFactory()
            ->createSearchFeedbackMapper()
            ->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer(), iterator_to_array($messageEntities), $hasSnapshot);
    }

    /**
     * The full snapshot payload — used only by the replay gateway action, deliberately kept out of
     * findTicketById()'s response (which only exposes the cheap hasSnapshot existence flag) so the Zed
     * ticket list/detail views never carry these potentially-large JSON blobs.
     *
     * @param int $idSearchFeedbackTicket
     */
    public function findSrpSnapshotByTicketId(int $idSearchFeedbackTicket): ?SearchFeedbackTicketSrpSnapshotTransfer
    {
        $snapshotEntity = $this->getFactory()
            ->createSearchFeedbackTicketSrpSnapshotQuery()
            ->filterByFkSearchFeedbackTicket($idSearchFeedbackTicket)
            ->findOne();

        if ($snapshotEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createSearchFeedbackMapper()
            ->mapSrpSnapshotEntityToTransfer($snapshotEntity, new SearchFeedbackTicketSrpSnapshotTransfer());
    }
}
