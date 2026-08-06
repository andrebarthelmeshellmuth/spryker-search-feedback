<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Zed;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;
use SprykerCommunity\Client\SearchFeedback\Zed\SearchFeedbackStub;

/**
 * The one thing worth protecting here: the exact gateway URL this stub calls, since a typo there fails
 * only at request time in a real shop (the Zed router 404s silently past this package's own test
 * boundary) — not at compile time or in any other test.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group Zed
 * @group SearchFeedbackStubTest
 */
class SearchFeedbackStubTest extends Unit
{
    public function testSubmitTicketCallsTheGatewaySubmitTicketEndpointAndReturnsItsResponse(): void
    {
        $requestTransfer = (new SearchFeedbackTicketRequestTransfer())->setTopic('relevance');
        $responseTransfer = (new SearchFeedbackTicketResponseTransfer())->setIsSuccess(true);

        $zedRequestClientMock = $this->createMock(SearchFeedbackToZedRequestInterface::class);
        $zedRequestClientMock->expects($this->once())
            ->method('call')
            ->with('/search-feedback/gateway/submit-ticket', $requestTransfer)
            ->willReturn($responseTransfer);

        $stub = new SearchFeedbackStub($zedRequestClientMock);

        $this->assertSame($responseTransfer, $stub->submitTicket($requestTransfer));
    }
}
