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
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface;

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
    protected const PARAM_STORE_NAME = 'storeName';

    /**
     * @var string
     */
    protected const PARAM_LOCALE_NAME = 'localeName';

    /**
     * @var string
     */
    protected const COL_CUSTOMER_EMAIL = 'customerEmail';

    /**
     * @var string
     */
    protected const COL_STATUS = 'status';

    /**
     * @var string
     */
    protected const COL_ACTIONS = 'actions';

    /**
     * storeName/localeName are OPTIONAL — this is a support-ticket triage view, not a per-scope
     * configuration page, so "no filter" (both null) is the useful default, same posture
     * search-ranking's own Metric History audit trail takes for the same reason.
     *
     * @param \Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery $ticketQuery
     * @param \SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface $customerFacade
     * @param string|null $storeName
     * @param string|null $localeName
     */
    public function __construct(
        protected SpySearchFeedbackTicketQuery $ticketQuery,
        protected SearchFeedbackGuiToCustomerFacadeInterface $customerFacade,
        protected ?string $storeName = null,
        protected ?string $localeName = null,
    ) {
        if ($this->storeName !== null) {
            $this->ticketQuery->filterByStoreName($this->storeName);
        }

        if ($this->localeName === null) {
            return;
        }

        $this->ticketQuery->filterByLocaleName($this->localeName);
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     */
    protected function configure(TableConfiguration $config): TableConfiguration
    {
        // The AJAX endpoint DataTables calls for every sort/page/search action is a fresh request that
        // never sees the request this Table was originally constructed for — the selected filter (if
        // any) has to be baked into that URL, same pattern search-ranking's own scoped tables use.
        // Omitted entirely (not just empty) when unset, so "no filter" round-trips as "no filter" rather
        // than becoming an explicit-empty-string filter on the next AJAX call.
        $urlParams = array_filter([
            static::PARAM_STORE_NAME => $this->storeName,
            static::PARAM_LOCALE_NAME => $this->localeName,
        ], static fn ($value): bool => $value !== null);

        if ($urlParams !== []) {
            $config->setUrl('table?' . http_build_query($urlParams));
        }

        $config->setHeader([
            SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET => 'ID',
            SpySearchFeedbackTicketTableMap::COL_TOPIC => 'Topic',
            SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM => 'Search term',
            static::COL_CUSTOMER_EMAIL => 'Customer',
            SpySearchFeedbackTicketTableMap::COL_STORE_NAME => 'Store',
            SpySearchFeedbackTicketTableMap::COL_LOCALE_NAME => 'Locale',
            static::COL_STATUS => 'Status',
            SpySearchFeedbackTicketTableMap::COL_CREATED_AT => 'Filed At',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSortable([
            SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET,
            SpySearchFeedbackTicketTableMap::COL_TOPIC,
            SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM,
            SpySearchFeedbackTicketTableMap::COL_STORE_NAME,
            SpySearchFeedbackTicketTableMap::COL_LOCALE_NAME,
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
                static::COL_CUSTOMER_EMAIL => $this->resolveCustomerEmail($ticketEntity->getCustomerReference()),
                SpySearchFeedbackTicketTableMap::COL_STORE_NAME => $ticketEntity->getStoreName(),
                SpySearchFeedbackTicketTableMap::COL_LOCALE_NAME => $ticketEntity->getLocaleName(),
                static::COL_STATUS => $this->formatStatus($ticketEntity),
                SpySearchFeedbackTicketTableMap::COL_CREATED_AT => $ticketEntity->getCreatedAt('Y-m-d H:i:s'),
                static::COL_ACTIONS => implode(' ', $this->createActionButtons($ticketEntity)),
            ];
        }

        return $rows;
    }

    /**
     * One `findCustomerByReference()` call per row — a real N+1 on this paged grid, accepted for now:
     * `spryker/customer` exposes no batch/collection lookup by multiple references, and this table's own
     * page size keeps the real cost small. Falls back to the raw reference (never blank) for a customer
     * account that no longer exists — the reference itself is still useful context even when the email
     * behind it is gone.
     *
     * @param string $customerReference
     */
    protected function resolveCustomerEmail(string $customerReference): string
    {
        $customerResponseTransfer = $this->customerFacade->findCustomerByReference($customerReference);

        if (!$customerResponseTransfer->getIsSuccess() || $customerResponseTransfer->getCustomerTransfer() === null) {
            return $customerReference;
        }

        return $customerResponseTransfer->getCustomerTransfer()->getEmail() ?? $customerReference;
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
