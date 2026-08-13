<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Persistence;

use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketMessageQuery;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketSrpSnapshotQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerCommunity\Zed\SearchFeedback\Persistence\Propel\Mapper\SearchFeedbackMapper;

class SearchFeedbackPersistenceFactory extends AbstractPersistenceFactory
{
    public function createSearchFeedbackTicketQuery(): SpySearchFeedbackTicketQuery
    {
        return SpySearchFeedbackTicketQuery::create();
    }

    public function createSearchFeedbackTicketMessageQuery(): SpySearchFeedbackTicketMessageQuery
    {
        return SpySearchFeedbackTicketMessageQuery::create();
    }

    public function createSearchFeedbackTicketSrpSnapshotQuery(): SpySearchFeedbackTicketSrpSnapshotQuery
    {
        return SpySearchFeedbackTicketSrpSnapshotQuery::create();
    }

    public function createSearchFeedbackMapper(): SearchFeedbackMapper
    {
        return new SearchFeedbackMapper();
    }
}
