<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
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

        return $this->getFactory()
            ->createSearchFeedbackMapper()
            ->mapTicketEntityToTransfer($ticketEntity, new SearchFeedbackTicketTransfer(), iterator_to_array($messageEntities));
    }
}
