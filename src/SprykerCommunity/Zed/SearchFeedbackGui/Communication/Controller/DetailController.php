<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedbackGui\Communication\Controller;

use Generated\Shared\Transfer\SearchFeedbackTicketMessageRequestTransfer;
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

        return $this->viewResponse([
            'ticket' => $ticketTransfer,
            'replyForm' => $replyForm->createView(),
            'statuses' => (new SearchFeedbackConfig())->getStatuses(),
        ]);
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
