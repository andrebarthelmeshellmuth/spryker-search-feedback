<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Client\SearchFeedback;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToSessionClientBridge;
use SprykerCommunity\Client\SearchFeedback\Dependency\Client\SearchFeedbackToZedRequestBridge;
use SprykerCommunity\Client\SearchFeedback\Search\SearchFeedbackSnapshotContext;

class SearchFeedbackDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_ZED_REQUEST = 'CLIENT_ZED_REQUEST';

    /**
     * @var string
     */
    public const CLIENT_SESSION = 'CLIENT_SESSION';

    /**
     * @var string
     */
    public const SNAPSHOT_CONTEXT = 'SNAPSHOT_CONTEXT';

    /**
     * Optional integration point: implementations live in a sibling package (e.g. search-ranking's
     * `SearchFeedbackTermVectorSnapshotProviderPlugin`), never registered by this package itself — a
     * project only gets termvector capture if it explicitly registers one here, same "ship the interface,
     * let the project wire the implementation" convention search-debug's own extension points use.
     *
     * @var string
     */
    public const TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS = 'TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS';

    /**
     * @param \Spryker\Client\Kernel\Container $container
     */
    #[\Override]
    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addZedRequestClient($container);
        $container = $this->addSessionClient($container);
        $container = $this->addSnapshotContext($container);

        return $this->addTermVectorSnapshotProviderPlugins($container);
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     */
    protected function addZedRequestClient(Container $container): Container
    {
        $container->set(static::CLIENT_ZED_REQUEST, fn (Container $container) => new SearchFeedbackToZedRequestBridge($container->getLocator()->zedRequest()->client()));

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     */
    protected function addSessionClient(Container $container): Container
    {
        $container->set(static::CLIENT_SESSION, fn (Container $container) => new SearchFeedbackToSessionClientBridge($container->getLocator()->session()->client()));

        return $container;
    }

    /**
     * A single instance per container (Spryker's Client `Container::set()` closures are memoized on first
     * access, same as the two bridges above) — the capture plugin and `SubmitTicketController`'s read path
     * both need to resolve the identical object within one request for the in-memory piece of this to work
     * at all (the cross-request piece is the session storage inside it).
     *
     * @param \Spryker\Client\Kernel\Container $container
     */
    protected function addSnapshotContext(Container $container): Container
    {
        $container->set(
            static::SNAPSHOT_CONTEXT,
            fn (Container $container) => new SearchFeedbackSnapshotContext($container->get(static::CLIENT_SESSION)),
        );

        return $container;
    }

    /**
     * Empty by default — see TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS.
     *
     * @param \Spryker\Client\Kernel\Container $container
     */
    protected function addTermVectorSnapshotProviderPlugins(Container $container): Container
    {
        $container->set(static::TERM_VECTOR_SNAPSHOT_PROVIDER_PLUGINS, fn () => $this->getTermVectorSnapshotProviderPlugins());

        return $container;
    }

    /**
     * @return array<\SprykerCommunity\Client\SearchFeedback\Dependency\Plugin\TermVectorSnapshotProviderPluginInterface>
     */
    protected function getTermVectorSnapshotProviderPlugins(): array
    {
        return [];
    }
}
