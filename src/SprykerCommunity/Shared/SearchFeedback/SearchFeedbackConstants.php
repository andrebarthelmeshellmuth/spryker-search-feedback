<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchFeedback;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface SearchFeedbackConstants
{
    /**
     * Specification:
     * - Toggles whether the `search-feedback:check-installation` Yves diagnostic page's route registers
     *   at all.
     * - Defaults to **disabled**: the route does not exist anywhere unless a project opts in. Fail-closed
     *   by default, matching both this package's own convention (every capability — plugins, routes, the
     *   permission itself — requires explicit registration, nothing auto-activates) and the identical
     *   flag on the sibling spryker-community/search-debug package
     *   ({@see \SprykerCommunity\Shared\SearchDebug\SearchDebugConstants::IS_CHECK_INSTALLATION_PAGE_ENABLED}),
     *   which itself follows Spryker core's own idiom for this kind of dev diagnostic
     *   (`Spryker\Shared\WebProfiler\WebProfilerConstants::IS_WEB_PROFILER_ENABLED` likewise defaults to
     *   `false`, turned on only in a dev-tier config).
     * - Set to `true` in a project's development-tier config (e.g. `config_default-development.php`) to
     *   opt in — the page is genuinely useful while wiring up the README's installation steps, so
     *   enabling it there is the recommended first thing to do, not an afterthought.
     * - The permission check in {@see \SprykerCommunity\Yves\SearchFeedbackWidget\Controller\CheckInstallationController}
     *   still applies wherever a project opts this flag on, so enabling it (even in a shared staging
     *   environment) does not by itself expose the page to anyone but a permitted customer. Not
     *   registering the route at all is still the stronger default, though: a permission check alone
     *   would still leak "this route exists and is gated" to an unauthenticated prober, which leaving the
     *   flag off entirely avoids.
     *
     * @api
     *
     * @var string
     */
    public const IS_CHECK_INSTALLATION_PAGE_ENABLED = 'SEARCH_FEEDBACK:IS_CHECK_INSTALLATION_PAGE_ENABLED';
}
