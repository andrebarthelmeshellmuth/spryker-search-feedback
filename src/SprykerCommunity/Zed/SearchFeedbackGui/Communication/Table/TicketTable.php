<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table;

use Orm\Zed\SearchFeedback\Persistence\Map\SpySearchFeedbackTicketTableMap;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * No row-level scoping by submitter — every Zed admin with access to this module sees every ticket (see
 * package README on the Zed-user/Yves-customer identity gap this deliberately avoids working around).
 */
class TicketTable extends AbstractTable
{
    /**
     * @var string
     */
    public const URL_PARAM_ID_SEARCH_FEEDBACK_TICKET = 'id-search-feedback-ticket';

    /**
     * @var string
     */
    protected const COL_STATUS = 'status';

    /**
     * @var string
     */
    protected const COL_ACTIONS = 'actions';

    /**
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery $ticketQuery
     */
    public function __construct(protected SpySearchFeedbackTicketQuery $ticketQuery)
    {
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     */
    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET => 'ID',
            SpySearchFeedbackTicketTableMap::COL_TOPIC => 'Topic',
            SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM => 'Search term',
            static::COL_STATUS => 'Status',
            SpySearchFeedbackTicketTableMap::COL_CREATED_AT => 'Filed At',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSortable([
            SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET,
            SpySearchFeedbackTicketTableMap::COL_TOPIC,
            SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM,
            SpySearchFeedbackTicketTableMap::COL_STATUS,
            SpySearchFeedbackTicketTableMap::COL_CREATED_AT,
        ]);

        $config->setSearchable([
            SpySearchFeedbackTicketTableMap::COL_TOPIC,
            SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM,
            SpySearchFeedbackTicketTableMap::COL_CUSTOMER_REFERENCE,
        ]);

        $config->setRawColumns([
            static::COL_STATUS,
            static::COL_ACTIONS,
        ]);

        $config->setDefaultSortField(SpySearchFeedbackTicketTableMap::COL_CREATED_AT, TableConfiguration::SORT_DESC);

        return $config;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $ticketEntities = $this->runQuery($this->ticketQuery, $config, true);
        $rows = [];

        /** @var \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket $ticketEntity */
        foreach ($ticketEntities as $ticketEntity) {
            $rows[] = [
                SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET => $ticketEntity->getIdSearchFeedbackTicket(),
                SpySearchFeedbackTicketTableMap::COL_TOPIC => $ticketEntity->getTopic(),
                SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM => $ticketEntity->getSearchTerm(),
                static::COL_STATUS => $this->formatStatus($ticketEntity),
                SpySearchFeedbackTicketTableMap::COL_CREATED_AT => $ticketEntity->getCreatedAt('Y-m-d H:i:s'),
                static::COL_ACTIONS => implode(' ', $this->createActionButtons($ticketEntity)),
            ];
        }

        return $rows;
    }

    /**
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket $ticketEntity
     */
    protected function formatStatus(SpySearchFeedbackTicket $ticketEntity): string
    {
        $cssClassesByStatus = [
            SearchFeedbackConfig::STATUS_OPEN => 'label-warning',
            SearchFeedbackConfig::STATUS_ANSWERED => 'label-info',
            SearchFeedbackConfig::STATUS_CLOSED => 'label-success',
        ];

        // 'label-default' is not a real class in this Zed GUI theme (renders unstyled) — fall back to
        // 'label-warning' instead, same as every status this map doesn't explicitly cover.
        $cssClass = $cssClassesByStatus[$ticketEntity->getStatus()] ?? 'label-warning';

        return $this->generateLabel(ucfirst($ticketEntity->getStatus()), $cssClass);
    }

    /**
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicket $ticketEntity
     *
     * @return array<string>
     */
    protected function createActionButtons(SpySearchFeedbackTicket $ticketEntity): array
    {
        $urlParams = [
            static::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET => $ticketEntity->getIdSearchFeedbackTicket(),
        ];

        return [
            $this->generateViewButton(
                Url::generate('/search-feedback-gui/detail', $urlParams)->build(),
                'View',
            ),
        ];
    }
}
