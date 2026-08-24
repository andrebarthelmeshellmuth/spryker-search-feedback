<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Glue\SearchFeedbackRestApi\Api\Storefront\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Api\Storefront\SearchFeedbackTicketsStorefrontResource;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface;
use SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Mapper\SearchFeedbackTicketsResourceMapperInterface;
use SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Processor\SearchFeedbackTicketsStorefrontProcessor;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @group SprykerCommunityTest
 * @group Glue
 * @group SearchFeedbackRestApi
 * @group SearchFeedbackTicketsStorefrontProcessorTest
 */
class SearchFeedbackTicketsStorefrontProcessorTest extends Unit
{
    public function testProcessPostThrowsWhenNoCustomerIsAuthenticated(): void
    {
        // Arrange
        $processor = $this->buildProcessor(
            searchFeedbackClientMock: $this->createMock(SearchFeedbackClientInterface::class),
            permissionClientMock: $this->createMock(PermissionClientInterface::class),
        );
        $request = $this->buildRequest(withCustomer: false);

        // Assert
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Not logged in.');

        // Act
        $processor->process(new SearchFeedbackTicketsStorefrontResource(), new Post(), [], ['request' => $request]);
    }

    public function testProcessPostThrowsWhenTheCustomerLacksThePermission(): void
    {
        // Arrange
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('can')
            ->with(SubmitSearchFeedbackTicketPermissionPlugin::KEY)
            ->willReturn(false);

        $processor = $this->buildProcessor(
            searchFeedbackClientMock: $this->createMock(SearchFeedbackClientInterface::class),
            permissionClientMock: $permissionClientMock,
        );
        $request = $this->buildRequest(withCustomer: true);

        // Assert
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Not authorized to submit search feedback tickets.');

        // Act
        $processor->process(new SearchFeedbackTicketsStorefrontResource(), new Post(), [], ['request' => $request]);
    }

    public function testProcessPostThrowsWithTheClientsErrorMessageWhenSubmissionFails(): void
    {
        // Arrange
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('can')->willReturn(true);

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('submitTicket')->willReturn(
            (new SearchFeedbackTicketResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage('Not authorized to rate search relevance.'),
        );

        $mapperMock = $this->createMock(SearchFeedbackTicketsResourceMapperInterface::class);
        $mapperMock->method('mapResourceToTicketRequestTransfer')
            ->willReturnCallback(fn (...$args) => $args[1]);

        $processor = $this->buildProcessor($searchFeedbackClientMock, $permissionClientMock, null, $mapperMock);
        $request = $this->buildRequest(withCustomer: true);

        // Assert
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Not authorized to rate search relevance.');

        // Act
        $processor->process(new SearchFeedbackTicketsStorefrontResource(), new Post(), [], ['request' => $request]);
    }

    public function testProcessPostReturnsTheDenormalizedResourceOnSuccess(): void
    {
        // Arrange
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('can')->willReturn(true);

        $ticketTransfer = (new SearchFeedbackTicketTransfer())
            ->setIdSearchFeedbackTicket(42)
            ->setTopic('Irrelevant results')
            ->setStatus('open');

        $searchFeedbackClientMock = $this->createMock(SearchFeedbackClientInterface::class);
        $searchFeedbackClientMock->method('submitTicket')->willReturn(
            (new SearchFeedbackTicketResponseTransfer())->setIsSuccess(true)->setTicket($ticketTransfer),
        );

        $mapperMock = $this->createMock(SearchFeedbackTicketsResourceMapperInterface::class);
        $mapperMock->method('mapResourceToTicketRequestTransfer')
            ->willReturnCallback(fn (...$args) => $args[1]->setTopic('Irrelevant results')->setBody('the body'));
        $mapperMock->method('mapTicketTransferToResourceData')
            ->with($ticketTransfer, 'the body')
            ->willReturn(['id' => '42', 'topic' => 'Irrelevant results']);

        $expectedResource = new SearchFeedbackTicketsStorefrontResource();

        $serializerMock = $this->createMock(SerializerServiceInterface::class);
        $serializerMock->method('denormalize')
            ->with(['id' => '42', 'topic' => 'Irrelevant results'], SearchFeedbackTicketsStorefrontResource::class)
            ->willReturn($expectedResource);

        $processor = $this->buildProcessor($searchFeedbackClientMock, $permissionClientMock, $serializerMock, $mapperMock);
        $request = $this->buildRequest(withCustomer: true);

        // Act
        $result = $processor->process(new SearchFeedbackTicketsStorefrontResource(), new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame($expectedResource, $result);
    }

    protected function buildProcessor(
        SearchFeedbackClientInterface $searchFeedbackClientMock,
        PermissionClientInterface $permissionClientMock,
        ?SerializerServiceInterface $serializerMock = null,
        ?SearchFeedbackTicketsResourceMapperInterface $mapperMock = null,
    ): SearchFeedbackTicketsStorefrontProcessor {
        return new SearchFeedbackTicketsStorefrontProcessor(
            $searchFeedbackClientMock,
            $permissionClientMock,
            $serializerMock ?? $this->createMock(SerializerServiceInterface::class),
            $mapperMock ?? $this->createMock(SearchFeedbackTicketsResourceMapperInterface::class),
        );
    }

    protected function buildRequest(bool $withCustomer): Request
    {
        $request = new Request();

        if ($withCustomer) {
            $request->attributes->set('CustomerTransfer', (new CustomerTransfer())->setCustomerReference('DE--123'));
        }

        $request->attributes->set('StoreTransfer', (new StoreTransfer())->setName('DE'));
        $request->attributes->set('LocaleTransfer', (new LocaleTransfer())->setLocaleName('de_DE'));

        return $request;
    }
}
