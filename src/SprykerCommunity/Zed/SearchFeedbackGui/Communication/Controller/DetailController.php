<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller;

use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketTransfer;
use OutOfBoundsException;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Form\ReplyForm;
use SprykerCommunity\Zed\SearchFeedbackGui\Communication\Table\TicketTable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerCommunity\Zed\SearchFeedbackGui\Communication\SearchFeedbackGuiCommunicationFactory getFactory()
 */
class DetailController extends AbstractController
{
    /**
     * @var string
     */
    protected const URL_TICKET_LIST = '/search-feedback-gui';

    /**
     * Both the "ticket worker" and "feedback admin" Zed ACL groups can reach this action and post a reply
     * — see the package README on why status-change is the ONLY capability split between them, gated by
     * {@see changeStatusAction()} being its own, independently ACL-restrictable action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request)
    {
        $idSearchFeedbackTicket = $this->castId(
            $request->query->get(TicketTable::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET),
        );

        $ticketTransfer = $this->getFactory()->getSearchFeedbackFacade()->findTicketById($idSearchFeedbackTicket);

        if ($ticketTransfer === null) {
            $this->addErrorMessage(sprintf('Ticket with id %d does not exist.', $idSearchFeedbackTicket));

            return $this->redirectResponse(static::URL_TICKET_LIST);
        }

        $replyForm = $this->getFactory()->createReplyForm()->handleRequest($request);

        if ($replyForm->isSubmitted() && $replyForm->isValid()) {
            $currentUserTransfer = $this->getFactory()->getUserFacade()->getCurrentUser();

            $messageRequestTransfer = (new SearchFeedbackTicketMessageRequestTransfer())
                ->setIdSearchFeedbackTicket($idSearchFeedbackTicket)
                ->setBody((string)$replyForm->getData()[ReplyForm::FIELD_BODY])
                ->setIdUser($currentUserTransfer->getIdUserOrFail());

            $this->getFactory()->getSearchFeedbackFacade()->replyToTicket($messageRequestTransfer);

            // A reply always moves an open ticket forward — closed/already-answered tickets are left as
            // they are rather than silently reopened by a reply, since only changeStatusAction() should
            // move a ticket backward or reopen one.
            if ($ticketTransfer->getStatusOrFail() === SearchFeedbackConfig::STATUS_OPEN) {
                $this->getFactory()->getSearchFeedbackFacade()->changeTicketStatus($idSearchFeedbackTicket, SearchFeedbackConfig::STATUS_ANSWERED);
            }

            $this->addSuccessMessage('Reply posted.');

            return $this->redirectResponse(sprintf('/search-feedback-gui/detail?%s=%d', TicketTable::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET, $idSearchFeedbackTicket));
        }

        // Deliberately resolved only on this path, not before the reply-submitted check above: a
        // successful reply redirects immediately and never renders this, so resolving it earlier would
        // mean paying for a findCustomerByReference() call per customer message in the thread on every
        // single successful reply, for a result that's thrown away.
        $customerEmailsByReference = [];
        $customerEmail = $this->resolveCustomerEmail($ticketTransfer->getCustomerReferenceOrFail(), $customerEmailsByReference);

        // One customer per ticket in practice (only the filer or a Zed user ever reply), but resolved
        // per message rather than assumed, so this stays correct even if that ever changes.
        foreach ($ticketTransfer->getMessages() as $messageTransfer) {
            if ($messageTransfer->getAuthorTypeOrFail() !== SearchFeedbackConfig::AUTHOR_TYPE_CUSTOMER) {
                continue;
            }

            $messageTransfer->setAuthorLabel(
                $this->resolveCustomerEmail($messageTransfer->getAuthorLabelOrFail(), $customerEmailsByReference),
            );
        }

        return $this->viewResponse([
            'ticket' => $ticketTransfer,
            'customerEmail' => $customerEmail,
            'replyForm' => $replyForm->createView(),
            'statuses' => (new SearchFeedbackConfig())->getStatuses(),
            'searchResultsPageUrl' => $this->buildSearchResultsPageUrl($ticketTransfer),
        ]);
    }

    /**
     * `spryker/customer` exposes no batch/collection lookup by multiple references, so this caches by
     * reference across the ticket's own messages instead of calling out per message.
     *
     * @param string $customerReference
     * @param array<string, string> $customerEmailsByReference
     */
    protected function resolveCustomerEmail(string $customerReference, array &$customerEmailsByReference): string
    {
        if (!isset($customerEmailsByReference[$customerReference])) {
            $customerResponseTransfer = $this->getFactory()->getCustomerFacade()->findCustomerByReference($customerReference);

            $customerEmailsByReference[$customerReference] = $customerResponseTransfer->getIsSuccess() && $customerResponseTransfer->getCustomerTransfer() !== null
                ? ($customerResponseTransfer->getCustomerTransfer()->getEmail() ?? $customerReference)
                : $customerReference;
        }

        return $customerEmailsByReference[$customerReference];
    }

    /**
     * The ticket already carries everything needed to reconstruct the exact SRP it was filed from
     * (`filters` is the real query string already parsed into an array — see
     * `SubmitTicketController::buildRedirectParameters()` in the Yves widget) — the only missing piece is
     * a Zed-side Yves host, since Zed has no host of its own to build a Yves link from.
     *
     * Branches on dynamic store mode the same way core's own
     * `Spryker\Zed\AvailabilityNotification\Business\Strategy\StoreYvesBaseUrlGetStrategy` does: a shop
     * with it OFF has one Yves host per store and no store segment in the URL; a shop with it ON shares
     * one Yves host across every store and carries the store as a URL path segment instead
     * (`StorePrefixRouterEnhancerPlugin`). Returns null (no link rendered) only in the OFF case when the
     * shop hasn't configured a host for the ticket's store — the ON case always has a base URL.
     *
     * When the ticket has a frozen snapshot, `srpReplayTicket` is appended so the Client-layer
     * `ReplayCapableSearch` decorator replays it instead of running a live search —
     * `SearchFeedbackReplayContextEventDispatcherPlugin` is what actually reads this param in Yves and
     * gates it behind `ViewSearchFeedbackTicketReplayPermissionPlugin`; a ticket with no snapshot gets the
     * plain live-search link exactly as before this feature existed.
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketTransfer $ticketTransfer
     */
    protected function buildSearchResultsPageUrl(SearchFeedbackTicketTransfer $ticketTransfer): ?string
    {
        $config = $this->getFactory()->getConfig();
        $languageCode = explode('_', $ticketTransfer->getLocaleNameOrFail())[0];

        if ($this->getFactory()->getStoreFacade()->isDynamicStoreEnabled()) {
            $yvesHost = $config->getBaseUrlYves();
            $pathPrefix = sprintf('/%s/%s', $ticketTransfer->getStoreNameOrFail(), $languageCode);
        } else {
            $yvesHost = $config->getStoreToYvesHostMapping()[$ticketTransfer->getStoreNameOrFail()] ?? null;
            $pathPrefix = '/' . $languageCode;
        }

        if (!$yvesHost) {
            return null;
        }

        $queryParameters = $ticketTransfer->getFilters() ?? [];

        if ($ticketTransfer->getHasSnapshot()) {
            $queryParameters[SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET] = $ticketTransfer->getIdSearchFeedbackTicketOrFail();
        }

        $queryString = http_build_query($queryParameters);

        return sprintf(
            '%s%s%s%s',
            rtrim($yvesHost, '/'),
            $pathPrefix,
            $config->getSearchPath(),
            $queryString !== '' ? '?' . $queryString : '',
        );
    }

    /**
     * Its own action (not folded into indexAction()) so a "feedback admin" Zed ACL group can be granted
     * this module's view+reply actions while having this one specifically denied — see the package README.
     *
     * Unlike indexAction(), this action never loads the ticket first — a stale bookmark or a hand-edited
     * URL can reach here with an id that no longer exists, so changeTicketStatus()'s
     * `OutOfBoundsException` is a real, reachable case here (not just a defensive check), caught and
     * turned into the same redirect-with-error-message UX the unknown-status branch above already uses.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function changeStatusAction(Request $request): RedirectResponse
    {
        $idSearchFeedbackTicket = $this->castId(
            $request->query->get(TicketTable::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET),
        );
        $status = (string)$request->query->get('status', '');

        if (!in_array($status, (new SearchFeedbackConfig())->getStatuses(), true)) {
            $this->addErrorMessage('Unknown ticket status.');

            return $this->redirectResponse(sprintf('/search-feedback-gui/detail?%s=%d', TicketTable::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET, $idSearchFeedbackTicket));
        }

        try {
            $this->getFactory()->getSearchFeedbackFacade()->changeTicketStatus($idSearchFeedbackTicket, $status);
        } catch (OutOfBoundsException) {
            $this->addErrorMessage(sprintf('Ticket with id %d does not exist.', $idSearchFeedbackTicket));

            return $this->redirectResponse(static::URL_TICKET_LIST);
        }

        $this->addSuccessMessage('Ticket status updated.');

        return $this->redirectResponse(sprintf('/search-feedback-gui/detail?%s=%d', TicketTable::URL_PARAM_ID_SEARCH_FEEDBACK_TICKET, $idSearchFeedbackTicket));
    }
}
