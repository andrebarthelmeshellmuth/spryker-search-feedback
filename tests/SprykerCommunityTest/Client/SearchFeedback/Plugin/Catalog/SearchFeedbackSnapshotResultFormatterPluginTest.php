<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback\Plugin\Catalog;

use Codeception\Test\Unit;
use Elastica\Query;
use Elastica\Response;
use Elastica\ResultSet;
use SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface;
use SprykerCommunity\Client\SearchFeedback\Plugin\Catalog\SearchFeedbackSnapshotResultFormatterPlugin;
use SprykerCommunity\Client\SearchFeedback\Search\SearchFeedbackSnapshotContext;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;

/**
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group Plugin
 * @group Catalog
 * @group SearchFeedbackSnapshotResultFormatterPluginTest
 * @group Portable
 */
class SearchFeedbackSnapshotResultFormatterPluginTest extends Unit
{
    public function testGetNameReturnsTheClassConstant(): void
    {
        $this->assertSame(SearchFeedbackSnapshotResultFormatterPlugin::NAME, (new SearchFeedbackSnapshotResultFormatterPlugin())->getName());
    }

    public function testFormatResultCapturesAndReturnsATokenForANormalSearch(): void
    {
        // Arrange
        $snapshotContextMock = $this->createMock(SearchFeedbackSnapshotContext::class);
        $snapshotContextMock->expects($this->once())
            ->method('capture')
            ->with('{"hits":{"hits":[]}}', $this->isType('string'), null, false, null)
            ->willReturn('a-real-token');

        $resultFormatterPlugin = $this->createResultFormatterPlugin($snapshotContextMock);
        $resultSet = new ResultSet(new Response(['hits' => ['hits' => []]]), Query::create(['query' => ['match_all' => []]]), []);

        // Act
        $result = $resultFormatterPlugin->formatResult($resultSet, []);

        // Assert
        $this->assertSame('a-real-token', $result[SearchFeedbackConfig::KEY_SNAPSHOT_TOKEN]);
    }

    /**
     * Regression test for a real crash found live: `ReplayCapableSearch::buildReplayResultSet()`
     * reconstructs its query via `Query::create($rawArrayFromStorage)` — a plain-array reconstruction, not
     * the fluent builder API a live query goes through. When the frozen query DSL contains a `suggest`
     * block, Elastica's own `Query::toArray()` throws `Undefined array key "suggest"` on that reconstructed
     * object, because `toArray()` expects the double-nested shape `setSuggest()` produces and the
     * reconstruction never re-nests it. Reproduced here with the exact shape that broke live: a top-level
     * `suggest` key, not the `suggest.suggest` nesting `toArray()` unwraps.
     *
     * The fix is to never call `getQuery()->toArray()` at all while replaying — there is nothing new worth
     * freezing while already looking at frozen data anyway — so this asserts BOTH that no exception is
     * thrown AND that capture() is never even attempted.
     */
    public function testFormatResultSkipsCaptureAndReturnsANullTokenWhenTheRequestIsItselfAReplay(): void
    {
        // Arrange
        $snapshotContextMock = $this->createMock(SearchFeedbackSnapshotContext::class);
        $snapshotContextMock->expects($this->never())->method('capture');

        $resultFormatterPlugin = $this->createResultFormatterPlugin($snapshotContextMock);

        // The exact query shape that crashed Query::toArray() live: a bare top-level "suggest" key, as
        // Query::create() on a plain array stores it verbatim without the setSuggest() double-nesting.
        // Decoded from JSON (like ReplayCapableSearch::buildReplayResultSet() decodes the stored query DSL)
        // rather than an inline literal array, so its type is a plain `array`, not a shape phpstan's
        // Elastica stub could narrow-check against — matching what Query::create() actually accepts at
        // runtime from real, untyped storage.
        /** @var array<string, mixed> $queryDslArray */
        $queryDslArray = json_decode('{"suggest":{"my-suggest":{"text":"foo"}}}', true);
        $resultSet = new ResultSet(new Response(['hits' => ['hits' => []]]), Query::create($queryDslArray), []);
        $requestParameters = [SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET => '42'];

        // Act
        $result = $resultFormatterPlugin->formatResult($resultSet, $requestParameters);

        // Assert
        $this->assertNull($result[SearchFeedbackConfig::KEY_SNAPSHOT_TOKEN]);
    }

    public function testFormatResultCapturesATermVectorSnapshotWhenAProviderPluginReturnsOne(): void
    {
        // Arrange
        $snapshotContextMock = $this->createMock(SearchFeedbackSnapshotContext::class);
        $snapshotContextMock->expects($this->once())
            ->method('capture')
            ->with($this->anything(), $this->anything(), $this->anything(), true, '{"relevanceWeight":0.5}')
            ->willReturn('a-real-token');

        $termVectorSnapshotProviderPluginMock = $this->createMock(TermVectorSnapshotProviderPluginInterface::class);
        $termVectorSnapshotProviderPluginMock->method('getTermVectorSnapshot')->willReturn('{"relevanceWeight":0.5}');

        $resultFormatterPlugin = $this->createResultFormatterPlugin($snapshotContextMock, [$termVectorSnapshotProviderPluginMock]);
        $resultSet = new ResultSet(new Response(['hits' => ['hits' => []]]), Query::create([]), []);

        // Act
        $resultFormatterPlugin->formatResult($resultSet, []);
    }

    /**
     * @param array<\SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface> $termVectorSnapshotProviderPlugins
     */
    protected function createResultFormatterPlugin(
        SearchFeedbackSnapshotContext $snapshotContextMock,
        array $termVectorSnapshotProviderPlugins = [],
    ): SearchFeedbackSnapshotResultFormatterPlugin {
        $searchFeedbackFactoryMock = $this->getMockBuilder(SearchFeedbackFactory::class)
            ->onlyMethods(['getSnapshotContext', 'getTermVectorSnapshotProviderPlugins'])
            ->getMock();
        $searchFeedbackFactoryMock->method('getSnapshotContext')->willReturn($snapshotContextMock);
        $searchFeedbackFactoryMock->method('getTermVectorSnapshotProviderPlugins')->willReturn($termVectorSnapshotProviderPlugins);

        $resultFormatterPlugin = new SearchFeedbackSnapshotResultFormatterPlugin();
        $resultFormatterPlugin->setFactory($searchFeedbackFactoryMock);

        return $resultFormatterPlugin;
    }
}
