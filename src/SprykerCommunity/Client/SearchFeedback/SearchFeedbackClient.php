<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory getFactory()
 */
class SearchFeedbackClient extends AbstractClient implements SearchFeedbackClientInterface
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
        return $this->getFactory()
            ->createSearchFeedbackStub()
            ->submitTicket($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer
     */
    public function getTicketSrpSnapshot(SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer): SearchFeedbackTicketSrpSnapshotResponseTransfer
    {
        return $this->getFactory()
            ->createSearchFeedbackStub()
            ->getTicketSrpSnapshot($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $token
     */
    public function consumeSnapshot(string $token): ?SearchFeedbackTicketSrpSnapshotTransfer
    {
        $snapshot = $this->getFactory()->getSnapshotContext()->consume($token);

        if ($snapshot === null) {
            return null;
        }

        return (new SearchFeedbackTicketSrpSnapshotTransfer())
            ->setRawResponse($snapshot['rawResponse'])
            ->setQueryDsl($snapshot['queryDsl'])
            ->setRequestParameters($snapshot['requestParameters'])
            ->setHasTermVectorSnapshot($snapshot['hasTermVectorSnapshot'])
            ->setTermVectorSnapshot($snapshot['termVectorSnapshot']);
    }
}
