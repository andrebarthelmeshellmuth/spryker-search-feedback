<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchFeedback\Business\Ticket;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketCollectionTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use InvalidArgumentException;
use OutOfBoundsException;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedback\Business\Ticket\TicketManager;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManagerInterface;
use SprykerCommunity\Zed\SearchFeedback\Persistence\SearchFeedbackRepositoryInterface;

/**
 * Pure unit tests, EntityManager/Repository mocked: covers the validation this class alone is responsible
 * for (empty body, unknown topic — see the class's own docblock on the permission re-check happening one
 * layer up, in `GatewayController`, not here) and that every method delegates to the right collaborator.
 * The real persistence behavior behind `createTicket()`/`addMessage()`/`changeStatus()` has its own
 * dedicated DB-backed coverage in {@see \SprykerCommunityTest\Zed\SearchFeedback\Persistence\SearchFeedbackEntityManagerTest}.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchFeedback
 * @group Business
 * @group TicketManagerTest
 * @group Portable
 */
class TicketManagerTest extends Unit
{
    public function testSubmitTicketRejectsAnEmptyBodyWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('createTicket');
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);

        $requestTransfer = $this->createRequestTransfer()->setBody('   ');

        // Act
        $responseTransfer = (new TicketManager($entityManagerMock, $repositoryMock))->submitTicket($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('A ticket needs a message body.', $responseTransfer->getErrorMessage());
    }

    public function testSubmitTicketRejectsAnUnknownTopicWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('createTicket');
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);

        $requestTransfer = $this->createRequestTransfer()->setTopic('not_a_real_topic');

        // Act
        $responseTransfer = (new TicketManager($entityManagerMock, $repositoryMock))->submitTicket($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Unknown ticket topic.', $responseTransfer->getErrorMessage());
    }

    public function testSubmitTicketDelegatesToTheEntityManagerAndReturnsASuccessResponseForAValidRequest(): void
    {
        // Arrange
        $requestTransfer = $this->createRequestTransfer();
        $createdTicketTransfer = (new SearchFeedbackTicketTransfer())->setIdSearchFeedbackTicket(42);

        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('createTicket')
            ->with($requestTransfer)
            ->willReturn($createdTicketTransfer);

        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);

        // Act
        $responseTransfer = (new TicketManager($entityManagerMock, $repositoryMock))->submitTicket($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccess());
        $this->assertSame($createdTicketTransfer, $responseTransfer->getTicket());
    }

    public function testReplyToTicketRejectsABlankBodyWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('addMessage');
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->never())->method('findTicketById');

        $messageRequestTransfer = (new SearchFeedbackTicketMessageRequestTransfer())
            ->setIdSearchFeedbackTicket(7)
            ->setBody('   ')
            ->setIdUser(3);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        (new TicketManager($entityManagerMock, $repositoryMock))->replyToTicket($messageRequestTransfer);
    }

    public function testReplyToTicketRejectsANonExistentTicketWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('addMessage');

        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->method('findTicketById')->with(999)->willReturn(null);

        $messageRequestTransfer = (new SearchFeedbackTicketMessageRequestTransfer())
            ->setIdSearchFeedbackTicket(999)
            ->setBody('An admin reply.')
            ->setIdUser(3);

        // Act & Assert
        $this->expectException(OutOfBoundsException::class);
        (new TicketManager($entityManagerMock, $repositoryMock))->replyToTicket($messageRequestTransfer);
    }

    public function testReplyToTicketAddsAZedUserMessageThenReReadsTheTicketFromTheRepository(): void
    {
        // Arrange
        $messageRequestTransfer = (new SearchFeedbackTicketMessageRequestTransfer())
            ->setIdSearchFeedbackTicket(7)
            ->setBody('An admin reply.')
            ->setIdUser(3);
        $reReadTicketTransfer = (new SearchFeedbackTicketTransfer())->setIdSearchFeedbackTicket(7);

        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('addMessage')
            ->with(7, 'An admin reply.', SearchFeedbackConfig::AUTHOR_TYPE_ZED_USER, 3, null);

        // Called twice: once for the existence check, once for the post-write re-read.
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->exactly(2))->method('findTicketById')->with(7)->willReturn($reReadTicketTransfer);

        // Act
        $resultTransfer = (new TicketManager($entityManagerMock, $repositoryMock))->replyToTicket($messageRequestTransfer);

        // Assert — the returned ticket comes from the fresh repository read, not built up locally.
        $this->assertSame($reReadTicketTransfer, $resultTransfer);
    }

    public function testChangeTicketStatusRejectsAnUnknownStatusWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('changeStatus');
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->never())->method('findTicketById');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        (new TicketManager($entityManagerMock, $repositoryMock))->changeTicketStatus(9, 'not_a_real_status');
    }

    public function testChangeTicketStatusRejectsANonExistentTicketWithoutTouchingTheEntityManager(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->never())->method('changeStatus');

        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->method('findTicketById')->with(999)->willReturn(null);

        // Act & Assert
        $this->expectException(OutOfBoundsException::class);
        (new TicketManager($entityManagerMock, $repositoryMock))->changeTicketStatus(999, SearchFeedbackConfig::STATUS_CLOSED);
    }

    public function testChangeTicketStatusDelegatesToTheEntityManagerThenReReadsTheTicketFromTheRepository(): void
    {
        // Arrange
        $reReadTicketTransfer = (new SearchFeedbackTicketTransfer())->setIdSearchFeedbackTicket(9)->setStatus(SearchFeedbackConfig::STATUS_CLOSED);

        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())->method('changeStatus')->with(9, SearchFeedbackConfig::STATUS_CLOSED);

        // Called twice: once for the existence check, once for the post-write re-read.
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->exactly(2))->method('findTicketById')->with(9)->willReturn($reReadTicketTransfer);

        // Act
        $resultTransfer = (new TicketManager($entityManagerMock, $repositoryMock))->changeTicketStatus(9, SearchFeedbackConfig::STATUS_CLOSED);

        // Assert
        $this->assertSame($reReadTicketTransfer, $resultTransfer);
    }

    public function testGetTicketCollectionIsAPlainPassthroughToTheRepository(): void
    {
        // Arrange
        $collectionTransfer = new SearchFeedbackTicketCollectionTransfer();

        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('getTicketCollection')->willReturn($collectionTransfer);

        // Act & Assert
        $this->assertSame($collectionTransfer, (new TicketManager($entityManagerMock, $repositoryMock))->getTicketCollection());
    }

    public function testFindTicketByIdIsAPlainPassthroughToTheRepository(): void
    {
        // Arrange
        $entityManagerMock = $this->createMock(SearchFeedbackEntityManagerInterface::class);
        $repositoryMock = $this->createMock(SearchFeedbackRepositoryInterface::class);
        $repositoryMock->expects($this->once())->method('findTicketById')->with(5)->willReturn(null);

        // Act & Assert
        $this->assertNull((new TicketManager($entityManagerMock, $repositoryMock))->findTicketById(5));
    }

    protected function createRequestTransfer(): SearchFeedbackTicketRequestTransfer
    {
        return (new SearchFeedbackTicketRequestTransfer())
            ->setTopic(SearchFeedbackConfig::TOPIC_RELEVANCE)
            ->setSearchTerm('chair')
            ->setFilters([])
            ->setPageNumber(1)
            ->setSkuList([])
            ->setBody('The results feel off.')
            ->setCustomerReference('CUST-TICKET-MANAGER-1')
            ->setStoreName('DE')
            ->setLocaleName('en_US');
    }
}
