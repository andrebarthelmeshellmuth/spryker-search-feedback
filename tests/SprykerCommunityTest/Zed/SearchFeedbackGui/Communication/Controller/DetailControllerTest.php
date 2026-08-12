<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGui\Communication\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use ReflectionMethod;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller\DetailController;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToStoreFacadeInterface;
use SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiConfig;
use SprykerCommunity\Zed\SearchFeedbackGui\SearchFeedbackGuiDependencyProvider;
use SprykerCommunityTest\Zed\SearchFeedbackGui\SearchFeedbackGuiZedTester;

/**
 * `resolveCustomerEmail()`/`buildSearchResultsPageUrl()` are `protected`, driven directly via Reflection
 * on a real `DetailController` — `getFactory()` resolves through the real Locator (only the Customer/
 * Store facades swapped via `DependencyHelper`, same pattern `GatewayControllerTest` already uses), and
 * `getConfig()`/`SearchFeedbackGuiConfig` are plain value objects needing no container at all. Real project
 * config (`SearchFeedbackGuiConstants::STORE_TO_YVES_HOST_MAPPING`, `ApplicationConstants::BASE_URL_YVES`)
 * is used as-is rather than mocked — the point of `buildSearchResultsPageUrl()`'s own tests is that it
 * builds the URL this shop's real config actually implies, for either dynamic-store-mode branch.
 *
 * `indexAction()`/`changeStatusAction()` are NOT covered here: both need the full Zed Silex app bootstrap
 * (`createReplyForm()`'s `form.factory` / `TicketTable::render()`'s Twig, see `TicketTableTest`'s own
 * docblock) — already covered end-to-end by the Zed GUI Presentation suite (`TicketGridAndDetailCest`,
 * `ManualStatusChangeCest`, `ConversationCest`, `EdgeCasesCest`).
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGui
 * @group Communication
 * @group Controller
 * @group DetailControllerTest
 *
 * @property \SprykerCommunityTest\Zed\SearchFeedbackGui\SearchFeedbackGuiZedTester $tester
 * @group Portable
 */
class DetailControllerTest extends Unit
{
    protected SearchFeedbackGuiZedTester $tester;

    public function testResolveCustomerEmailReturnsTheEmailOnASuccessfulLookup(): void
    {
        $this->stubCustomerFacade($this->createSuccessfulCustomerResponse('shopper@example.com'));

        $customerEmailsByReference = [];
        $email = $this->invokeProtected('resolveCustomerEmail', ['CUST-REF-1', &$customerEmailsByReference]);

        $this->assertSame('shopper@example.com', $email);
        $this->assertSame(['CUST-REF-1' => 'shopper@example.com'], $customerEmailsByReference);
    }

    public function testResolveCustomerEmailFallsBackToTheReferenceWhenTheLookupFails(): void
    {
        $this->stubCustomerFacade((new CustomerResponseTransfer())->setIsSuccess(false));

        $customerEmailsByReference = [];
        $email = $this->invokeProtected('resolveCustomerEmail', ['CUST-REF-GONE', &$customerEmailsByReference]);

        $this->assertSame('CUST-REF-GONE', $email);
    }

    public function testResolveCustomerEmailReusesAnAlreadyResolvedReferenceWithoutCallingTheFacadeAgain(): void
    {
        $customerFacadeMock = $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class);
        $customerFacadeMock->expects($this->never())->method('findCustomerByReference');
        $this->tester->setDependency(SearchFeedbackGuiDependencyProvider::FACADE_CUSTOMER, $customerFacadeMock);

        $customerEmailsByReference = ['CUST-REF-CACHED' => 'cached@example.com'];
        $email = $this->invokeProtected('resolveCustomerEmail', ['CUST-REF-CACHED', &$customerEmailsByReference]);

        $this->assertSame('cached@example.com', $email);
    }

    public function testBuildSearchResultsPageUrlUsesThePerStoreYvesHostWhenDynamicStoreModeIsDisabled(): void
    {
        $this->stubStoreFacade(false);

        $ticketTransfer = $this->createTicketTransfer('DE', 'de_DE', ['q' => 'chair']);
        $url = $this->invokeProtected('buildSearchResultsPageUrl', [$ticketTransfer]);

        $expectedHost = (new SearchFeedbackGuiConfig())->getStoreToYvesHostMapping()['DE'] ?? null;

        if ($expectedHost === null) {
            $this->assertNull($url, 'No configured host for this store means no link, not a crash.');

            return;
        }

        $this->assertSame(rtrim($expectedHost, '/') . '/de/search?q=chair', $url);
    }

    public function testBuildSearchResultsPageUrlReturnsNullWhenDynamicStoreModeIsDisabledAndNoHostIsConfiguredForTheStore(): void
    {
        $this->stubStoreFacade(false);

        $ticketTransfer = $this->createTicketTransfer('NON-EXISTENT-STORE', 'de_DE', []);
        $url = $this->invokeProtected('buildSearchResultsPageUrl', [$ticketTransfer]);

        $this->assertNull($url);
    }

    public function testBuildSearchResultsPageUrlUsesTheSharedYvesHostWithAStorePathSegmentWhenDynamicStoreModeIsEnabled(): void
    {
        $this->stubStoreFacade(true);

        $ticketTransfer = $this->createTicketTransfer('DE', 'en_US', []);
        $url = $this->invokeProtected('buildSearchResultsPageUrl', [$ticketTransfer]);

        $expectedHost = (new SearchFeedbackGuiConfig())->getBaseUrlYves();
        $this->assertSame(rtrim($expectedHost, '/') . '/DE/en/search', $url);
    }

    protected function stubCustomerFacade(CustomerResponseTransfer $customerResponseTransfer): void
    {
        $customerFacadeMock = $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class);
        $customerFacadeMock->method('findCustomerByReference')->willReturn($customerResponseTransfer);
        $this->tester->setDependency(SearchFeedbackGuiDependencyProvider::FACADE_CUSTOMER, $customerFacadeMock);
    }

    protected function stubStoreFacade(bool $isDynamicStoreEnabled): void
    {
        $storeFacadeMock = $this->createMock(SearchFeedbackGuiToStoreFacadeInterface::class);
        $storeFacadeMock->method('isDynamicStoreEnabled')->willReturn($isDynamicStoreEnabled);
        $this->tester->setDependency(SearchFeedbackGuiDependencyProvider::FACADE_STORE, $storeFacadeMock);
    }

    protected function createSuccessfulCustomerResponse(string $email): CustomerResponseTransfer
    {
        return (new CustomerResponseTransfer())
            ->setIsSuccess(true)
            ->setCustomerTransfer((new CustomerTransfer())->setEmail($email));
    }

    /**
     * @param array<string, mixed> $filters
     */
    protected function createTicketTransfer(string $storeName, string $localeName, array $filters): SearchFeedbackTicketTransfer
    {
        return (new SearchFeedbackTicketTransfer())
            ->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE)
            ->setSearchTerm('chair')
            ->setStoreName($storeName)
            ->setLocaleName($localeName)
            ->setFilters($filters);
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    protected function invokeProtected(string $method, array $arguments)
    {
        $reflectionMethod = new ReflectionMethod(DetailController::class, $method);

        return $reflectionMethod->invokeArgs(new DetailController(), $arguments);
    }
}
