<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Search;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToSessionClientInterface;
use SprykerCommunity\Client\SearchFeedback\Search\SearchFeedbackSnapshotContext;

/**
 * A plain in-memory array standing in for real session storage — sufficient here since this class's own
 * job is exactly "read/write/evict these three keys correctly", not session persistence itself (that's
 * spryker/session's job, already tested there).
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group SearchFeedbackSnapshotContextTest
 * @group Portable
 */
class SearchFeedbackSnapshotContextTest extends Unit
{
    public function testCaptureThenConsumeRoundTripsTheSameData(): void
    {
        // Arrange
        $sessionClientMock = $this->createInMemorySessionClientMock();
        $context = new SearchFeedbackSnapshotContext($sessionClientMock);

        // Act
        $token = $context->capture('{"hits":[]}', '{"query":{}}', '{"q":"chair"}', true, '{"relevanceWeight":1.2}');
        $snapshot = $context->consume($token);

        // Assert
        $this->assertNotNull($snapshot);
        $this->assertSame('{"hits":[]}', $snapshot['rawResponse']);
        $this->assertSame('{"query":{}}', $snapshot['queryDsl']);
        $this->assertSame('{"q":"chair"}', $snapshot['requestParameters']);
        $this->assertTrue($snapshot['hasTermVectorSnapshot']);
        $this->assertSame('{"relevanceWeight":1.2}', $snapshot['termVectorSnapshot']);
    }

    public function testConsumeIsOneTimeUse(): void
    {
        // Arrange
        $sessionClientMock = $this->createInMemorySessionClientMock();
        $context = new SearchFeedbackSnapshotContext($sessionClientMock);
        $token = $context->capture('{}', '{}', null, false, null);

        // Act
        $context->consume($token);
        $secondConsume = $context->consume($token);

        // Assert
        $this->assertNull($secondConsume);
    }

    public function testConsumeReturnsNullForAnUnknownToken(): void
    {
        // Arrange
        $sessionClientMock = $this->createInMemorySessionClientMock();
        $context = new SearchFeedbackSnapshotContext($sessionClientMock);

        // Act & Assert
        $this->assertNull($context->consume('never-captured-token'));
    }

    public function testCapturingMoreThanTheMaxPendingSnapshotsEvictsTheOldestFirst(): void
    {
        // Arrange
        $sessionClientMock = $this->createInMemorySessionClientMock();
        $context = new SearchFeedbackSnapshotContext($sessionClientMock);

        // Act — one more than MAX_PENDING_SNAPSHOTS (5)
        $tokens = [];

        for ($i = 0; $i < 6; $i++) {
            $tokens[] = $context->capture((string)$i, '{}', null, false, null);
        }

        // Assert — the first (oldest) token was evicted, the rest survive
        $this->assertNull($context->consume($tokens[0]));
        $this->assertNotNull($context->consume($tokens[5]));
    }

    protected function createInMemorySessionClientMock(): SearchFeedbackToSessionClientInterface
    {
        $storage = [];

        $sessionClientMock = $this->createMock(SearchFeedbackToSessionClientInterface::class);
        $sessionClientMock->method('set')->willReturnCallback(function (string $name, $value) use (&$storage): void {
            $storage[$name] = $value;
        });
        $sessionClientMock->method('get')->willReturnCallback(function (string $name, $default = null) use (&$storage) {
            return $storage[$name] ?? $default;
        });
        $sessionClientMock->method('remove')->willReturnCallback(function (string $name) use (&$storage): void {
            unset($storage[$name]);
        });

        return $sessionClientMock;
    }
}
