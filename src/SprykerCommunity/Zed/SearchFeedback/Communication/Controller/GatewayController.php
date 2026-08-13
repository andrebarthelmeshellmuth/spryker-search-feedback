<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchFeedback\Communication\Controller;

use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotResponseTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;

/**
 * @method \SprykerCommunity\Zed\SearchFeedback\Business\SearchFeedbackFacadeInterface getFacade()
 * @method \SprykerCommunity\Zed\SearchFeedback\Communication\SearchFeedbackCommunicationFactory getFactory()
 */
class GatewayController extends AbstractGatewayController
{
    /**
     * The single authorization gate for this write, independently re-checked here rather than trusted
     * from the Yves side alone — same posture as search-ranking-optimizer's own GatewayController: Zed has
     * the only trustworthy source of "does this customer actually hold this permission."
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer $requestTransfer
     */
    public function submitTicketAction(SearchFeedbackTicketRequestTransfer $requestTransfer): SearchFeedbackTicketResponseTransfer
    {
        $isAuthorized = $this->getFactory()
            ->createCompanyUserPermissionAuthorizer()
            ->isAuthorized($requestTransfer->getCustomerReferenceOrFail(), SubmitSearchFeedbackTicketPermissionPlugin::KEY);

        if (!$isAuthorized) {
            return (new SearchFeedbackTicketResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage('Not authorized to submit search feedback tickets.');
        }

        return $this->getFacade()->submitTicket($requestTransfer);
    }

    /**
     * Same independent-re-check posture as submitTicketAction() — the Yves-side permission check in
     * `SearchFeedbackReplayContextEventDispatcherPlugin` is a UX gate (bounce to login early), not the
     * authorization boundary; this is.
     *
     * @param \Generated\Shared\Transfer\SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer
     */
    public function getTicketSrpSnapshotAction(SearchFeedbackTicketSrpSnapshotRequestTransfer $requestTransfer): SearchFeedbackTicketSrpSnapshotResponseTransfer
    {
        $isAuthorized = $this->getFactory()
            ->createCompanyUserPermissionAuthorizer()
            ->isAuthorized($requestTransfer->getCustomerReferenceOrFail(), ViewSearchFeedbackTicketReplayPermissionPlugin::KEY);

        if (!$isAuthorized) {
            return (new SearchFeedbackTicketSrpSnapshotResponseTransfer())->setIsFound(false);
        }

        $snapshotTransfer = $this->getFacade()->findSrpSnapshotByTicketId($requestTransfer->getIdSearchFeedbackTicketOrFail());

        if ($snapshotTransfer === null) {
            return (new SearchFeedbackTicketSrpSnapshotResponseTransfer())->setIsFound(false);
        }

        return (new SearchFeedbackTicketSrpSnapshotResponseTransfer())
            ->setIsFound(true)
            ->setSnapshot($snapshotTransfer);
    }
}
