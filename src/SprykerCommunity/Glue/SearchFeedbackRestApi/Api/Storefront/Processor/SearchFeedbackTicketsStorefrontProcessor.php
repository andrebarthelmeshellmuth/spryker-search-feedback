<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Processor;

use Generated\Api\Storefront\SearchFeedbackTicketsStorefrontResource;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClientInterface;
use SprykerCommunity\Glue\SearchFeedbackRestApi\Api\Storefront\Mapper\SearchFeedbackTicketsResourceMapperInterface;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SearchFeedbackTicketsStorefrontProcessor extends AbstractStorefrontProcessor
{
    /**
     * @var string
     */
    protected const MESSAGE_NOT_AUTHORIZED = 'Not authorized to submit search feedback tickets.';

    /**
     * @var string
     */
    protected const MESSAGE_NOT_LOGGED_IN = 'Not logged in.';

    public function __construct(
        protected SearchFeedbackClientInterface $searchFeedbackClient,
        protected PermissionClientInterface $permissionClient,
        protected SerializerServiceInterface $serializer,
        protected SearchFeedbackTicketsResourceMapperInterface $searchFeedbackTicketsResourceMapper,
    ) {
    }

    /**
     * The `PermissionClientInterface::can()` check below is a UX-level fast-fail only — same posture as
     * `SubmitTicketController::submitAction()` (Yves), which performs the identical check for the identical
     * reason: the real, unbypassable authorization happens server-side in Zed's `GatewayController`, which
     * independently re-resolves the customer's permission rather than trusting anything asserted here (see
     * `CompanyUserPermissionAuthorizer`). A dedicated Symfony Voter (the pattern
     * `CustomerOwnershipVoter` in customers-rest-api uses for `is_granted('CUSTOMER_OWNER')`) was considered
     * and rejected here: a Voter would only move this exact same non-authoritative check into the
     * `security:` YAML expression, at the cost of a new architectural component, for a permission this
     * package already has a working Client-side accessor for. Checking inline keeps the same single-purpose
     * Client dependency already used elsewhere in this package, and lets the response carry a specific,
     * consistent 422/error-message pair instead of a generic 403.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    protected function processPost(mixed $data): mixed
    {
        if (!$this->hasCustomer()) {
            throw new UnprocessableEntityHttpException(static::MESSAGE_NOT_LOGGED_IN);
        }

        if (!$this->permissionClient->can(SubmitSearchFeedbackTicketPermissionPlugin::KEY)) {
            throw new UnprocessableEntityHttpException(static::MESSAGE_NOT_AUTHORIZED);
        }

        /** @var \Generated\Api\Storefront\SearchFeedbackTicketsStorefrontResource $ticketResource */
        $ticketResource = $data;
        $requestTransfer = $this->searchFeedbackTicketsResourceMapper->mapResourceToTicketRequestTransfer(
            $ticketResource,
            new SearchFeedbackTicketRequestTransfer(),
        );
        $requestTransfer
            ->setCustomerReference($this->getCustomerReference())
            ->setStoreName((string)$this->findStoreName())
            ->setLocaleName((string)$this->findLocaleName());

        $responseTransfer = $this->searchFeedbackClient->submitTicket($requestTransfer);

        if (!$responseTransfer->getIsSuccess() || $responseTransfer->getTicket() === null) {
            throw new UnprocessableEntityHttpException(
                $responseTransfer->getErrorMessage() ?: 'Ticket submission failed.',
            );
        }

        $resourceData = $this->searchFeedbackTicketsResourceMapper->mapTicketTransferToResourceData(
            $responseTransfer->getTicket(),
            $requestTransfer->getBodyOrFail(),
        );

        return $this->serializer->denormalize($resourceData, SearchFeedbackTicketsStorefrontResource::class);
    }
}
