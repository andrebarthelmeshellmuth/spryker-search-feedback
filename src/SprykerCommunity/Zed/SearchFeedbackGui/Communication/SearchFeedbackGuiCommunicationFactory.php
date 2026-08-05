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
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToSearchFeedbackFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiDependencyProvider;
use Symfony\Component\Form\FormInterface;

class SearchFeedbackGuiCommunicationFactory extends AbstractCommunicationFactory
{
    public function createTicketTable(): TicketTable
    {
        return new TicketTable(SpySearchFeedbackTicketQuery::create());
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
}
