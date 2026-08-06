<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback;

use Codeception\Test\Unit;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * `getTopics()`/`getStatuses()` back both the Yves-side topic dropdown and the server-side validation in
 * `TicketManager::submitTicket()`/`DetailController::changeStatusAction()` — a constant added to the class
 * but forgotten in one of these two getters would silently desync the two, which a plain constant-value
 * test would not catch.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group SearchFeedbackConfigTest
 */
class SearchFeedbackConfigTest extends Unit
{
    public function testGetTopicsListsEveryDeclaredTopicConstant(): void
    {
        $topics = (new SearchFeedbackConfig())->getTopics();

        $this->assertEqualsCanonicalizing([
            SearchFeedbackConfig::TOPIC_RELEVANCE,
            SearchFeedbackConfig::TOPIC_MISSING_RESULTS,
            SearchFeedbackConfig::TOPIC_WRONG_ORDER,
            SearchFeedbackConfig::TOPIC_FACETS,
            SearchFeedbackConfig::TOPIC_OTHER,
        ], $topics);
    }

    public function testGetStatusesListsEveryDeclaredStatusConstantInLifecycleOrder(): void
    {
        $statuses = (new SearchFeedbackConfig())->getStatuses();

        $this->assertSame([
            SearchFeedbackConfig::STATUS_OPEN,
            SearchFeedbackConfig::STATUS_ANSWERED,
            SearchFeedbackConfig::STATUS_CLOSED,
        ], $statuses);
    }
}
