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

    /**
     * WebDriver has no native "POST to a URL with these fields" primitive — a real browser only ever
     * submits a POST via an actual `<form>` on the page. Used by tests that need to reach a CSRF-protected
     * POST endpoint with input no real button on the page ever produces (e.g. a deliberately bogus field
     * value), by injecting and submitting an equivalent throwaway form via JS instead.
     *
     * @param string $actionUrl
     * @param array<string, string> $fields
     */
    public function submitFormViaJs(string $actionUrl, array $fields): void
    {
        $inputsHtml = '';

        foreach ($fields as $name => $value) {
            $inputsHtml .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars($name, ENT_QUOTES),
                htmlspecialchars($value, ENT_QUOTES),
            );
        }

        $formHtml = sprintf(
            '<form id="js-test-post-form" method="post" action="%s">%s</form>',
            htmlspecialchars($actionUrl, ENT_QUOTES),
            $inputsHtml,
        );

        $this->executeJS(sprintf(
            'document.body.insertAdjacentHTML("beforeend", %s); document.getElementById("js-test-post-form").submit();',
            json_encode($formHtml),
        ));
    }
}
