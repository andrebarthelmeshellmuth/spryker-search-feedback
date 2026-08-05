<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchFeedback;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class SearchFeedbackConfig extends AbstractSharedConfig
{
    /**
     * @var string
     */
    public const TOPIC_RELEVANCE = 'relevance';

    /**
     * @var string
     */
    public const TOPIC_MISSING_RESULTS = 'missing_results';

    /**
     * @var string
     */
    public const TOPIC_WRONG_ORDER = 'wrong_order';

    /**
     * @var string
     */
    public const TOPIC_FACETS = 'facets';

    /**
     * @var string
     */
    public const TOPIC_OTHER = 'other';

    /**
     * @var string
     */
    public const STATUS_OPEN = 'open';

    /**
     * @var string
     */
    public const STATUS_ANSWERED = 'answered';

    /**
     * @var string
     */
    public const STATUS_CLOSED = 'closed';

    /**
     * @var string
     */
    public const AUTHOR_TYPE_CUSTOMER = 'customer';

    /**
     * @var string
     */
    public const AUTHOR_TYPE_ZED_USER = 'zed_user';

    /**
     * @return array<string>
     */
    public function getTopics(): array
    {
        return [
            static::TOPIC_RELEVANCE,
            static::TOPIC_MISSING_RESULTS,
            static::TOPIC_WRONG_ORDER,
            static::TOPIC_FACETS,
            static::TOPIC_OTHER,
        ];
    }

    /**
     * @return array<string>
     */
    public function getStatuses(): array
    {
        return [
            static::STATUS_OPEN,
            static::STATUS_ANSWERED,
            static::STATUS_CLOSED,
        ];
    }
}
