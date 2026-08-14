<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\EventDispatcher;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerCommunity\Shared\SearchFeedback\Plugin\ViewSearchFeedbackTicketReplayPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Gates a replay link (`?srpReplayTicket=<id>`, appended by `DetailController::buildSearchResultsPageUrl()`
 * for a ticket that has a snapshot) behind {@see ViewSearchFeedbackTicketReplayPermissionPlugin}, mirroring
 * the pattern {@see \SprykerCommunity\Yves\SearchDebug\Plugin\EventDispatcher\SearchDebugContextEventDispatcherPlugin}
 * (in the sibling search-debug package) already established for reading a request param this early.
 *
 * Deliberately different behavior from that plugin, though: search-debug's flag is a purely additive
 * presentational toggle, so it silently no-ops when unpermitted. A replay request that fails this check
 * throws {@see AccessDeniedException} instead — Symfony's own security exception listener resolves that
 * two ways depending on authentication state: an unauthenticated visitor gets bounced through login and
 * lands back here afterward (`target_path`, no custom redirect plumbing needed), while an authenticated
 * customer who simply lacks the permission gets a 403. Both are the intended UX for a link that's meant to
 * work for a specific Yves company-user login, confirmed with the package's design discussion.
 *
 * This plugin does NOT strip/re-verify the raw ticket id the way search-debug strips+resets its boolean
 * flag — the id is not the security boundary here. `ReplayCapableSearch`/the Zed gateway independently
 * re-check authorization against the request's actual customer identity before ever returning snapshot
 * data, so a spoofed id (or a bypass of this Yves-layer gate entirely) still can't read another customer's
 * ticket snapshot; this plugin only provides the early, friendly login-redirect UX.
 */
class SearchFeedbackReplayContextEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    use PermissionAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $container is mandated by EventDispatcherPluginInterface.
     *
     * @param \Spryker\Shared\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param \Spryker\Service\Container\ContainerInterface $container
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addListener(KernelEvents::REQUEST, function (RequestEvent $event): void {
            $this->handleRequest($event);
        });

        return $eventDispatcher;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException Not logged in (redirected to
     * login by Symfony's own security handling) or logged in without the replay permission (403).
     */
    protected function handleRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->query->has(SearchFeedbackConfig::REQUEST_PARAM_SRP_REPLAY_TICKET)) {
            return;
        }

        if (!$this->can(ViewSearchFeedbackTicketReplayPermissionPlugin::KEY)) {
            throw new AccessDeniedException('Not authorized to view a search feedback ticket replay.');
        }
    }
}
