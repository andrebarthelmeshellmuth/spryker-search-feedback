<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication;

use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Form\ReplyForm;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table\TicketTable;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToLocaleFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToSearchFeedbackFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiDependencyProvider;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiConfig getConfig()
 */
class SearchFeedbackGuiCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @param string|null $storeName
     * @param string|null $localeName
     */
    public function createTicketTable(?string $storeName = null, ?string $localeName = null): TicketTable
    {
        return new TicketTable(SpySearchFeedbackTicketQuery::create(), $this->getCustomerFacade(), $storeName, $localeName);
    }

    public function createReplyForm(): FormInterface
    {
        return $this->getFormFactory()->create(ReplyForm::class);
    }

    public function getSearchFeedbackFacade(): SearchFeedbackGuiToSearchFeedbackFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackGuiDependencyProvider::FACADE_SEARCH_FEEDBACK);
    }

    public function getUserFacade(): SearchFeedbackGuiToUserFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackGuiDependencyProvider::FACADE_USER);
    }

    public function getCustomerFacade(): SearchFeedbackGuiToCustomerFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackGuiDependencyProvider::FACADE_CUSTOMER);
    }

    public function getStoreFacade(): SearchFeedbackGuiToStoreFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackGuiDependencyProvider::FACADE_STORE);
    }

    public function getLocaleFacade(): SearchFeedbackGuiToLocaleFacadeInterface
    {
        return $this->getProvidedDependency(SearchFeedbackGuiDependencyProvider::FACADE_LOCALE);
    }

    /**
     * Every store name, for the Store/Locale filter the ticket list page shows.
     *
     * @return array<string>
     */
    public function getAllStoreNames(): array
    {
        return array_map(
            static fn ($storeTransfer) => $storeTransfer->getNameOrFail(),
            $this->getStoreFacade()->getAllStores(),
        );
    }

    /**
     * Every locale available in this shop, for the same filter — deliberately NOT filtered to the
     * selected store's own locales, same posture search-ranking's own scope selectors take.
     *
     * @return array<string>
     */
    public function getAllLocaleNames(): array
    {
        return array_values($this->getLocaleFacade()->getAvailableLocales());
    }
}
