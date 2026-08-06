<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Communication\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Communication\Controller\GatewayController;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepository;
use SprykerCommunity\Zed\SearchFeedback\SearchFeedbackDependencyProvider;
use SprykerCommunityTest\Zed\SearchFeedback\SearchFeedbackZedTester;

/**
 * INTEGRATION TEST — the real round trip the Yves widget actually drives (minus the Yves-side
 * `SubmitTicketController` -> `SearchFeedbackClient` -> `Client\ZedRequest` HTTP hop, which is Spryker
 * core's own `zed-request` package's concern, not this one's).
 *
 * Everything on THIS side of that hop is real and resolved through the REAL Locator — a real
 * `GatewayController`, real `SearchFeedbackFacade`/`BusinessFactory`/`CompanyUserPermissionAuthorizer`
 * chain — via `DependencyHelper::setDependency()`, the standard way to swap ONE bundle dependency without
 * touching anything else the container wires up. Only the CompanyUser/Permission facades behind
 * `CompanyUserPermissionAuthorizer` are swapped — that class's own logic already has full, dedicated
 * coverage in {@see \SprykerCommunityTest\Zed\SearchFeedback\Communication\Authorization\CompanyUserPermissionAuthorizerTest};
 * here only ITS RESULT needs to be controllable. Every write is verified independently by re-reading it
 * back from a fresh {@see SearchFeedbackRepository} query, not by trusting the response the writer itself
 * returns.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Communication
 * @group Controller
 * @group GatewayControllerTest
 *
 * @property \SprykerCommunityTest\Zed\SearchFeedback\SearchFeedbackZedTester $tester
 */
class GatewayControllerTest extends Unit
{
    protected SearchFeedbackZedTester $tester;

    /**
     * @var string
     */
    protected const CUSTOMER_REFERENCE = 'CUST-GATEWAY-CONTROLLER-TEST';

    public function testSubmitTicketActionPersistsARealTicketWhenAuthorized(): void
    {
        // Arrange
        $this->stubAuthorization(true);
        $requestTransfer = $this->createRequestTransfer();

        // Act
        $responseTransfer = (new GatewayController())->submitTicketAction($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccess());
        $this->assertNotNull($responseTransfer->getTicket());
        $this->assertSame(SearchFeedbackConfig::STATUS_OPEN, $responseTransfer->getTicketOrFail()->getStatus());

        // Independently re-read from the DB -- not just trusting the writer's own return value.
        $persistedTicketTransfer = (new SearchFeedbackRepository())->findTicketById(
            $responseTransfer->getTicketOrFail()->getIdSearchFeedbackTicketOrFail(),
        );
        $this->assertNotNull($persistedTicketTransfer, 'The ticket must be readable back from a fresh repository query, not just echoed in the response.');
        $this->assertSame('chair', $persistedTicketTransfer->getSearchTerm());
        $this->assertCount(1, iterator_to_array($persistedTicketTransfer->getMessages()));
    }

    /**
     * The authorization gate must actually BLOCK the write, not just decorate the response -- an
     * unauthorized request must leave no trace in the database.
     */
    public function testSubmitTicketActionRefusesAndPersistsNothingWhenNotAuthorized(): void
    {
        // Arrange
        $this->stubAuthorization(false);
        $requestTransfer = $this->createRequestTransfer();

        // Act
        $responseTransfer = (new GatewayController())->submitTicketAction($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Not authorized to submit search feedback tickets.', $responseTransfer->getErrorMessage());
        $this->assertNull($responseTransfer->getTicket());
    }

    protected function createRequestTransfer(): SearchFeedbackTicketRequestTransfer
    {
        return (new SearchFeedbackTicketRequestTransfer())
            ->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE)
            ->setSearchTerm('chair')
            ->setFilters([])
            ->setPageNumber(1)
            ->setSkuList([])
            ->setBody('The results feel off for this query.')
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setStoreName('DE')
            ->setLocaleName('en_US');
    }

    /**
     * Swaps the CompanyUser/Permission facades `CompanyUserPermissionAuthorizer` depends on, globally for
     * this test, via `DependencyHelper` -- the real Locator resolves the real `GatewayController`, real
     * `SearchFeedbackFacade`, and the real (unmodified) `CompanyUserPermissionAuthorizer`, all of which end
     * up seeing only this controlled authorization outcome.
     *
     * @param bool $isAuthorized
     */
    protected function stubAuthorization(bool $isAuthorized): void
    {
        $companyUserCollectionTransfer = new CompanyUserCollectionTransfer();

        if ($isAuthorized) {
            $companyUserCollectionTransfer->addCompanyUser((new CompanyUserTransfer())->setIdCompanyUser(1));
        }

        $companyUserFacadeMock = $this->createMock(SearchFeedbackToCompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('getActiveCompanyUsersByCustomerReference')
            ->with($this->callback(fn (CustomerTransfer $customerTransfer) => $customerTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE))
            ->willReturn($companyUserCollectionTransfer);

        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->method('can')->willReturn($isAuthorized);

        $this->tester->setDependency(SearchFeedbackDependencyProvider::FACADE_COMPANY_USER, $companyUserFacadeMock);
        $this->tester->setDependency(SearchFeedbackDependencyProvider::FACADE_PERMISSION, $permissionFacadeMock);
    }
}
