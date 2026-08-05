<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback;

use Spryker\Client\Kernel\AbstractFactory;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;
use SprykerCommunity\Client\SearchFeedback\Zed\SearchFeedbackStub;
use SprykerCommunity\Client\SearchFeedback\Zed\SearchFeedbackStubInterface;

class SearchFeedbackFactory extends AbstractFactory
{
    public function createSearchFeedbackStub(): SearchFeedbackStubInterface
    {
        return new SearchFeedbackStub($this->getZedRequestClient());
    }

    public function getZedRequestClient(): SearchFeedbackToZedRequestInterface
    {
        return $this->getProvidedDependency(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
