<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Communication\Authorization;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerCommunity\Zed\SearchFeedback\Communication\Authorization\CompanyUserPermissionAuthorizer;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToCompanyUserFacadeInterface;
use SprykerCommunity\Zed\SearchFeedback\Dependency\Facade\SearchFeedbackToPermissionFacadeInterface;

/**
 * Same class and purpose as search-ranking-optimizer's own `CompanyUserPermissionAuthorizer` (see this
 * class's own docblock) — this test is deliberately the same shape as that package's
 * `CompanyUserPermissionAuthorizerTest`, adapted to this package's namespaces.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Communication
 * @group Authorization
 * @group CompanyUserPermissionAuthorizerTest
 * @group Portable
 */
class CompanyUserPermissionAuthorizerTest extends Unit
{
    public function testIsAuthorizedNeverTrustsAnIdentifierFromTheRequestItselfAndAlwaysResolvesViaCompanyUserFacade(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(SearchFeedbackToCompanyUserFacadeInterface::class);
        $companyUserFacadeMock->expects($this->once())
            ->method('getActiveCompanyUsersByCustomerReference')
            ->with($this->callback(fn (CustomerTransfer $customerTransfer): bool => $customerTransfer->getCustomerReference() === 'CUST-1'))
            ->willReturn(
                (new CompanyUserCollectionTransfer())->addCompanyUser((new CompanyUserTransfer())->setIdCompanyUser(42)),
            );

        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->expects($this->once())->method('can')->with('SomePermission', 42)->willReturn(true);

        $authorizer = new CompanyUserPermissionAuthorizer($companyUserFacadeMock, $permissionFacadeMock);

        // Act
        $result = $authorizer->isAuthorized('CUST-1', 'SomePermission');

        // Assert
        $this->assertTrue($result);
    }

    public function testIsAuthorizedGrantsAccessWhenAnyOfMultipleActiveCompanyUsersHoldsThePermission(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(SearchFeedbackToCompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('getActiveCompanyUsersByCustomerReference')->willReturn(
            (new CompanyUserCollectionTransfer())
                ->addCompanyUser((new CompanyUserTransfer())->setIdCompanyUser(1))
                ->addCompanyUser((new CompanyUserTransfer())->setIdCompanyUser(2)),
        );

        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->method('can')->willReturnMap([
            ['SomePermission', 1, false],
            ['SomePermission', 2, true],
        ]);

        $authorizer = new CompanyUserPermissionAuthorizer($companyUserFacadeMock, $permissionFacadeMock);

        // Act
        $result = $authorizer->isAuthorized('CUST-1', 'SomePermission');

        // Assert
        $this->assertTrue($result);
    }

    public function testIsAuthorizedReturnsFalseWhenTheCustomerHasNoActiveCompanyUserAtAll(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(SearchFeedbackToCompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('getActiveCompanyUsersByCustomerReference')->willReturn(new CompanyUserCollectionTransfer());

        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->expects($this->never())->method('can');

        $authorizer = new CompanyUserPermissionAuthorizer($companyUserFacadeMock, $permissionFacadeMock);

        // Act
        $result = $authorizer->isAuthorized('CUST-1', 'SomePermission');

        // Assert
        $this->assertFalse($result);
    }

    public function testIsAuthorizedReturnsFalseWhenNoActiveCompanyUserHoldsThePermission(): void
    {
        // Arrange
        $companyUserFacadeMock = $this->createMock(SearchFeedbackToCompanyUserFacadeInterface::class);
        $companyUserFacadeMock->method('getActiveCompanyUsersByCustomerReference')->willReturn(
            (new CompanyUserCollectionTransfer())->addCompanyUser((new CompanyUserTransfer())->setIdCompanyUser(1)),
        );

        $permissionFacadeMock = $this->createMock(SearchFeedbackToPermissionFacadeInterface::class);
        $permissionFacadeMock->method('can')->willReturn(false);

        $authorizer = new CompanyUserPermissionAuthorizer($companyUserFacadeMock, $permissionFacadeMock);

        // Act
        $result = $authorizer->isAuthorized('CUST-1', 'SomePermission');

        // Assert
        $this->assertFalse($result);
    }
}
