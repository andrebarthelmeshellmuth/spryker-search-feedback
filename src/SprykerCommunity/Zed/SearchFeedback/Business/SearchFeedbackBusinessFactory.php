<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerCommunity\Zed\SearchFeedback\Business\Ticket\TicketManager;
use SprykerCommunity\Zed\SearchFeedback\Business\Ticket\TicketManagerInterface;

/**
 * @method \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManagerInterface getEntityManager()
 * @method \SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepositoryInterface getRepository()
 */
class SearchFeedbackBusinessFactory extends AbstractBusinessFactory
{
    public function createTicketManager(): TicketManagerInterface
    {
        return new TicketManager($this->getEntityManager(), $this->getRepository());
    }
}
