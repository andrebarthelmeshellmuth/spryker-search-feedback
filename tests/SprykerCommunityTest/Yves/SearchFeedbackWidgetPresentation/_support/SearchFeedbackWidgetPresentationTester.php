<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation;

use Codeception\Actor;
use Exception;
use PDO;
use SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\PageObject\LoginPage;

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
 * @SuppressWarnings(\SprykerCommunityTest\Yves\SearchFeedbackWidgetPresentation\PHPMD)
 */
class SearchFeedbackWidgetPresentationTester extends Actor
{
    use _generated\SearchFeedbackWidgetPresentationTesterActions;

    /**
     * The account this demoshop's fixtures grant SubmitSearchFeedbackTicketPermissionPlugin to — same
     * account used by the sibling search-debug/search-ranking-optimizer suites (it holds all three
     * packages' own permissions), see data/import/common/common/company_role_permission.csv.
     *
     * @var string
     */
    public const PERMITTED_CUSTOMER_EMAIL = 'search-admin@test-company.example';

    /**
     * Same company (test-company) as the permitted customer, but with no company role assignment at
     * all — confirmed against company_user.csv/company_user_role.csv (customer_reference DE--1 has no
     * role, unlike search-admin's DE--35).
     *
     * @var string
     */
    public const UNPERMITTED_CUSTOMER_EMAIL = 'spencor.hopkin@acme.com';

    /**
     * @var string
     */
    public const CUSTOMER_PASSWORD = 'change123';

    /**
     * @param string $email
     */
    public function loginAsCustomer(string $email): void
    {
        // WebDriver keeps the browser session across Cests in this suite (restart: false), so a
        // prior test's login can still be active here - log out first or the login form never renders.
        $this->amOnPage('/logout');
        $this->amOnPage(LoginPage::URL);
        $this->submitForm(['name' => 'loginForm'], [
            LoginPage::FORM_FIELD_EMAIL => $email,
            LoginPage::FORM_FIELD_PASSWORD => static::CUSTOMER_PASSWORD,
        ]);
    }

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
     * Direct PDO, not Propel/a Facade: this test process is Yves-context, and calling a Zed Facade
     * from there hits exactly the layer-crossing gotcha that already shipped one real bug this
     * session ({@see \SprykerCommunity\Client\SearchRanking\SearchRankingFactory} equivalent —
     * Spryker's Locator is layer-specific). A bare PDO connection using the same
     * SPRYKER_DB_* env vars the app itself boots with has no such dependency.
     *
     * @param string $storeName
     * @param string $localeName
     * @param float $relevanceWeight
     */
    public function setSearchRankingRelevanceWeight(string $storeName, string $localeName, float $relevanceWeight): void
    {
        $connection = $this->createDirectDatabaseConnection();
        $statement = $connection->prepare(
            'UPDATE spy_search_ranking_setting SET setting_value = :settingValue WHERE setting_key = :settingKey AND store_name = :storeName AND locale_name = :localeName',
        );
        $statement->execute([
            'settingValue' => (string)$relevanceWeight,
            'settingKey' => 'relevance_weight',
            'storeName' => $storeName,
            'localeName' => $localeName,
        ]);
    }

    /**
     * Runs the same two hand-run steps a lean docker/sdk setup needs after any weight change
     * (search-ranking:normalize, then draining the sync queue) — this demoshop has no scheduler
     * auto-consuming them. Shells out rather than calling the Zed console Application in-process for
     * the same layer-crossing reason as {@see setSearchRankingRelevanceWeight()}.
     */
    public function publishSearchRankingSettings(): void
    {
        $consolePath = escapeshellarg(APPLICATION_ROOT_DIR . '/vendor/bin/console');
        shell_exec($consolePath . ' search-ranking:normalize 2>&1');
        shell_exec($consolePath . ' queue:worker:start --stop-when-empty 2>&1');
    }

    /**
     * @param string $customerReference
     *
     * @throws \Exception
     *
     * @return int
     */
    public function grabLatestSearchFeedbackTicketIdForCustomerReference(string $customerReference): int
    {
        $connection = $this->createDirectDatabaseConnection();
        $statement = $connection->prepare(
            'SELECT id_search_feedback_ticket FROM spy_search_feedback_ticket WHERE customer_reference = :customerReference ORDER BY id_search_feedback_ticket DESC LIMIT 1',
        );
        $statement->execute(['customerReference' => $customerReference]);
        $idSearchFeedbackTicket = $statement->fetchColumn();

        if ($idSearchFeedbackTicket === false) {
            throw new Exception(sprintf('No search feedback ticket found for customer reference "%s".', $customerReference));
        }

        return (int)$idSearchFeedbackTicket;
    }

    protected function createDirectDatabaseConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            getenv('SPRYKER_DB_HOST'),
            getenv('SPRYKER_DB_PORT'),
            getenv('SPRYKER_DB_DATABASE'),
        );

        return new PDO($dsn, (string)getenv('SPRYKER_DB_USERNAME'), (string)getenv('SPRYKER_DB_PASSWORD'));
    }
}
