<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedbackGui\Communication\Table;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Orm\Zed\SearchFeedback\Persistence\Map\SpySearchFeedbackTicketTableMap;
use Orm\Zed\SearchFeedback\Persistence\SpySearchFeedbackTicketQuery;
use ReflectionMethod;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table\TicketTable;
use SprykerCommunity\Zed\SearchFeedbackGui\Dependency\Facade\SearchFeedbackGuiToCustomerFacadeInterface;

/**
 * `configure()` and `resolveCustomerEmail()` are `protected` — driven directly via Reflection, which
 * needs no app context and is safe here.
 *
 * `prepareData()` and `formatStatus()` are deliberately NOT covered this way: `prepareData()` reads
 * `$this->request` (only set by `AbstractTable::init()`, which pulls the current `Request` from the
 * Zed Silex application container) and calls `formatStatus()`/`createActionButtons()`, which in turn
 * call `AbstractTable::getTwig()` — `Spryker\Shared\Kernel\Container\GlobalContainer`, a process-wide
 * singleton only populated once the full Zed application has bootstrapped. Confirmed empirically:
 * `RuntimeException: GlobalContainer has not been initialized` / `Attempt to read property "query" on
 * null`, neither available under Codeception's `Environment`/`LocatorHelper` alone. Same posture
 * `ReplyFormTest`'s own docblock documents for `createReplyForm()`. This full path (real DB rows,
 * customer-email resolution, per-status label CSS, the view-button link) already has real coverage via
 * the Zed GUI Presentation suite (`TicketGridAndDetailCest`, `ManualStatusChangeCest`).
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedbackGui
 * @group Communication
 * @group Table
 * @group TicketTableTest
 */
class TicketTableTest extends Unit
{
    public function testConfigureSetsHeaderSortableAndSearchableColumns(): void
    {
        $config = $this->invokeConfigure($this->createTicketTable());

        $this->assertArrayHasKey(SpySearchFeedbackTicketTableMap::COL_ID_SEARCH_FEEDBACK_TICKET, $config->getHeader());
        $this->assertContains(SpySearchFeedbackTicketTableMap::COL_SEARCH_TERM, $config->getSearchable());
        $this->assertContains(SpySearchFeedbackTicketTableMap::COL_CREATED_AT, $config->getSortable());
    }

    public function testConfigureOmitsTheUrlQueryStringWhenNeitherStoreNorLocaleIsGiven(): void
    {
        $config = $this->invokeConfigure($this->createTicketTable());

        $this->assertNull($config->getUrl());
    }

    public function testConfigureBakesStoreAndLocaleIntoTheUrlQueryStringWhenBothAreGiven(): void
    {
        $config = $this->invokeConfigure($this->createTicketTable('DE', 'de_DE'));

        $this->assertSame('table?storeName=DE&localeName=de_DE', $config->getUrl());
    }

    public function testResolveCustomerEmailReturnsTheEmailOnASuccessfulLookup(): void
    {
        $customerFacadeMock = $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class);
        $customerFacadeMock->method('findCustomerByReference')->willReturn(
            (new CustomerResponseTransfer())
                ->setIsSuccess(true)
                ->setCustomerTransfer((new CustomerTransfer())->setEmail('shopper@example.com')),
        );

        $email = $this->invokeProtected(
            $this->createTicketTable(null, null, $customerFacadeMock),
            'resolveCustomerEmail',
            ['CUST-REF-1'],
        );

        $this->assertSame('shopper@example.com', $email);
    }

    public function testResolveCustomerEmailFallsBackToTheReferenceWhenTheLookupFails(): void
    {
        $customerFacadeMock = $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class);
        $customerFacadeMock->method('findCustomerByReference')->willReturn(
            (new CustomerResponseTransfer())->setIsSuccess(false),
        );

        $email = $this->invokeProtected(
            $this->createTicketTable(null, null, $customerFacadeMock),
            'resolveCustomerEmail',
            ['CUST-REF-GONE'],
        );

        $this->assertSame('CUST-REF-GONE', $email);
    }

    protected function createTicketTable(
        ?string $storeName = null,
        ?string $localeName = null,
        ?SearchFeedbackGuiToCustomerFacadeInterface $customerFacade = null,
    ): TicketTable {
        return new TicketTable(
            SpySearchFeedbackTicketQuery::create(),
            $customerFacade ?? $this->createMock(SearchFeedbackGuiToCustomerFacadeInterface::class),
            $storeName,
            $localeName,
        );
    }

    protected function invokeConfigure(TicketTable $ticketTable): TableConfiguration
    {
        return $this->invokeProtected($ticketTable, 'configure', [new TableConfiguration()]);
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    protected function invokeProtected(TicketTable $ticketTable, string $method, array $arguments)
    {
        $reflectionMethod = new ReflectionMethod(TicketTable::class, $method);

        return $reflectionMethod->invokeArgs($ticketTable, $arguments);
    }
}
