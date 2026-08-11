<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade;

interface SearchFeedbackGuiToLocaleFacadeInterface
{
    /**
     * @return array<string> [id_locale => locale_name] pairs.
     */
    public function getAvailableLocales(): array;
}
