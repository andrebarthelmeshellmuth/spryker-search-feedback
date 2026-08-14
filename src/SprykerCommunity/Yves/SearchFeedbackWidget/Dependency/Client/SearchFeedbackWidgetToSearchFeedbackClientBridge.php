<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Dependency\Client;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotTransfer;

class SearchFeedbackWidgetToSearchFeedbackClientBridge implements SearchFeedbackWidgetToSearchFeedbackClientInterface
{
    /**
     * @var \SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface
     */
    protected $searchFeedbackClient;

    /**
     * @param \SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface $searchFeedbackClient
     */
    public function __construct($searchFeedbackClient)
    {
        $this->searchFeedbackClient = $searchFeedbackClient;
    }

    public function submitTicket(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer
    {
        return $this->searchFeedbackClient->submitTicket($requestTransfer);
    }

    public function consumeSnapshot(string $token): ?SearchFeedbackTicketSrpSnapshotTransfer
    {
        return $this->searchFeedbackClient->consumeSnapshot($token);
    }
}
