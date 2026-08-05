<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Yves\SearchFeedbackWidget\Plugin\Twig;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerCommunity\Shared\SearchFeedback\Plugin\SubmitSearchFeedbackTicketPermissionPlugin;
use SprykerCommunity\Shared\SearchFeedback\SearchFeedbackConfig;
use Twig\Environment;
use Twig\TwigFunction;

/**
 * @method \SprykerCommunity\Yves\SearchFeedbackWidget\SearchFeedbackWidgetFactory getFactory()
 *
 * Registers `canSubmitSearchFeedbackTicket()` so the ticket-form include can gate itself on the Feedback
 * Admin permission without a project-level controller edit — same reasoning
 * `SearchRankingOptimizerWidgetTwigPlugin::canRateSearchRelevance()` already established.
 *
 * Also registers `searchFeedbackTicketCsrfToken()`: the submit endpoint is a plain POST controller, not
 * bound to a Symfony Form, so it carries none of the CSRF protection a Form-backed POST gets automatically
 * — same `CsrfTokenManagerInterface` mechanism the search-ranking-optimizer widget uses for its own
 * non-Form POST action.
 */
class SearchFeedbackWidgetTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    use PermissionAwareTrait;

    /**
     * @var string
     */
    public const FUNCTION_NAME_CAN_SUBMIT_TICKET = 'canSubmitSearchFeedbackTicket';

    /**
     * @var string
     */
    public const FUNCTION_NAME_TICKET_CSRF_TOKEN = 'searchFeedbackTicketCsrfToken';

    /**
     * @var string
     */
    public const FUNCTION_NAME_GET_TOPICS = 'getSearchFeedbackTicketTopics';

    /**
     * @var string
     */
    public const CSRF_TOKEN_ID = 'search-feedback-widget-submit-ticket';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $container is mandated by TwigPluginInterface.
     *
     * @param \Twig\Environment $twig
     * @param \Spryker\Service\Container\ContainerInterface $container
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_CAN_SUBMIT_TICKET,
            fn (): bool => $this->can(SubmitSearchFeedbackTicketPermissionPlugin::KEY),
        ));

        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_TICKET_CSRF_TOKEN,
            fn (): string => $this->getFactory()->getCsrfTokenManager()->getToken(static::CSRF_TOKEN_ID)->getValue(),
        ));

        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_GET_TOPICS,
            /**
             * @return array<string>
             */
            fn (): array => (new SearchFeedbackConfig())->getTopics(),
        ));

        return $twig;
    }
}
