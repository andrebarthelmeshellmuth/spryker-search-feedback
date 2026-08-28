<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Client\SearchFeedback;

use Codeception\Test\Unit;
use Elastica\Query;
use Elastica\Response;
use Elastica\ResultSet;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Plugin\Catalog\SearchFeedbackSnapshotResultFormatterPlugin;
use SprykerCommunity\Client\SearchFeedback\Search\SearchFeedbackSnapshotContext;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackClient;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackDependencyProvider;
use SprykerCommunity\Client\SearchFeedback\SearchFeedbackFactory;
use SprykerCommunityTest\Client\SearchFeedback\Fixture\FakeTermVectorSnapshotProviderPlugin;
use SprykerCommunityTest\Client\SearchFeedback\Fixture\FakeTermVectorSnapshotRestorerPlugin;

/**
 * The provider/restorer seam is owned by this package (see {@see \SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface}),
 * so its capture and restore paths must be exercisable against a real implementation without pulling in
 * `spryker-community/search-ranking` — the only package that ships one in practice — as a test
 * dependency. These two tests use the in-repo {@see FakeTermVectorSnapshotProviderPlugin} /
 * {@see FakeTermVectorSnapshotRestorerPlugin} to prove the string produced at capture time is exactly
 * what a registered restorer receives on replay, end to end, with nothing from search-ranking on the
 * classpath.
 *
 * @group SprykerCommunityTest
 * @group Client
 * @group SearchFeedback
 * @group TermVectorSnapshotStandaloneRoundTripTest
 * @group Portable
 */
class TermVectorSnapshotStandaloneRoundTripTest extends Unit
{
    /**
     * @var string
     */
    protected const SNAPSHOT_JSON = '{"relevanceWeight":0.42}';

    public function testAConcreteProviderPluginFeedsItsSnapshotIntoTheCapturePath(): void
    {
        // Arrange
        $snapshotContextMock = $this->createMock(SearchFeedbackSnapshotContext::class);
        $snapshotContextMock->expects($this->once())
            ->method('capture')
            ->with($this->anything(), $this->anything(), $this->anything(), true, static::SNAPSHOT_JSON)
            ->willReturn('a-real-token');

        $factoryMock = $this->getMockBuilder(SearchFeedbackFactory::class)
            ->onlyMethods(['getSnapshotContext', 'getTermVectorSnapshotProviderPlugins'])
            ->getMock();
        $factoryMock->method('getSnapshotContext')->willReturn($snapshotContextMock);
        $factoryMock->method('getTermVectorSnapshotProviderPlugins')
            ->willReturn([new FakeTermVectorSnapshotProviderPlugin(static::SNAPSHOT_JSON)]);

        $resultFormatterPlugin = new SearchFeedbackSnapshotResultFormatterPlugin();
        $resultFormatterPlugin->setFactory($factoryMock);

        $resultSet = new ResultSet(new Response(['hits' => ['hits' => []]]), Query::create([]), []);

        // Act & Assert — the `with(..., true, SNAPSHOT_JSON)` expectation is the assertion.
        $resultFormatterPlugin->formatResult($resultSet, []);
    }

    public function testAConcreteRestorerPluginReceivesTheExactSnapshotStringOnReplay(): void
    {
        // Arrange
        $restorerPlugin = new FakeTermVectorSnapshotRestorerPlugin();

        $container = new Container();
        $container->set(
            SearchFeedbackDependencyProvider::TERM_VECTOR_SNAPSHOT_RESTORER_PLUGINS,
            fn () => [$restorerPlugin],
        );

        $factory = new SearchFeedbackFactory();
        $factory->setContainer($container);

        $client = new SearchFeedbackClient();
        $client->setFactory($factory);

        // Act
        $client->restoreTermVectorSnapshot(static::SNAPSHOT_JSON);

        // Assert
        $this->assertSame([static::SNAPSHOT_JSON], $restorerPlugin->restoredSnapshots);
    }
}
