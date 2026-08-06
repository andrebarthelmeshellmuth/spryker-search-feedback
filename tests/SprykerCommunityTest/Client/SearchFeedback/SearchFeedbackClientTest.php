<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SearchFeedbackTicketRequestTransfer;
use Generated\Shared\Transfer\SearchFeedbackTicketResponseTransfer;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestInterface;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClient;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackDependencyProvider;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory;

/**
 * The one thing worth protecting at this layer: `submitTicket()` really delegates to the factory-built
 * stub and returns exactly what it returns, unmodified — `SearchFeedbackStubTest` already covers the
 * stub's own HTTP-call behavior, so this test's job is only the one hop above it.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group SearchFeedbackClientTest
 */
class SearchFeedbackClientTest extends Unit
{
    public function testSubmitTicketDelegatesToTheFactoryBuiltStubAndReturnsItsResponseUnmodified(): void
    {
        // Arrange
        $requestTransfer = (new SearchFeedbackTicketRequestTransfer())->setTopic('relevance');
        $responseTransfer = (new SearchFeedbackTicketResponseTransfer())->setIsSuccess(true);

        $zedRequestClientMock = $this->createMock(SearchFeedbackToZedRequestInterface::class);
        $zedRequestClientMock->method('call')
            ->with('/search-feedback/gateway/submit-ticket', $requestTransfer)
            ->willReturn($responseTransfer);

        $container = new Container();
        $container->set(SearchFeedbackDependencyProvider::CLIENT_ZED_REQUEST, $zedRequestClientMock);

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act & Assert
        $this->assertSame($responseTransfer, $client->submitTicket($requestTransfer));
    }
}
