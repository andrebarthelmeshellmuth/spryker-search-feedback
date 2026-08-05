<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation;

use Codeception\Actor;
use Exception;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(\SprykerCommunityTest\Zed\SearchFeedbackGuiPresentation\PHPMD)
 */
class SearchFeedbackGuiPresentationTester extends Actor
{
    use _generated\SearchFeedbackGuiPresentationTesterActions;

    /**
     * @param string $selector
     */
    public function tryToSeeElement(string $selector): bool
    {
        try {
            $this->seeElement($selector);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
