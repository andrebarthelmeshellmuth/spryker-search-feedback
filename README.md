# Spryker Search Feedback

A voice-of-customer capture tool for the search results page (SRP): an authorized storefront customer can
file a free-text ticket about a set of search results — tied to the query term, active filters, page
number, and the SKUs actually shown on that page, plus a topic — and Zed back-office admins hold a
conversation about it from there. The Yves side is write-only: once a ticket is submitted there is no way
to read it, or any reply, back from the storefront. Everything past submission happens in Zed.

*Part of the [Search Relevance](https://search-relevance.dev/) project.*

## Contents

- [Why a separate package](#why-a-separate-package)
- [Status](#status)
- [What it does](#what-it-does)
- [Data model](#data-model)
- [Installation](#installation)
- [Testing and CI](#testing-and-ci)
  - [Automated checks](#automated-checks)
  - [Test suite](#test-suite)
  - [Known coverage gaps](#known-coverage-gaps)
  - [Browser (Presentation) suite](#browser-presentation-suite)
- [License](#license)

## Why a separate package

Structurally close to [spryker-community/search-ranking-optimizer](https://github.com/andrebarthelmeshellmuth/spryker-search-ranking-optimizer)'s
SRP relevance-rating widget (Yves widget → Gateway-controller write → Zed persistence), but a different
bounded context: this is qualitative support/VoC ticketing, not a numeric ranking signal. It has no
dependency on `search-ranking`/`search-ranking-optimizer`'s tuning machinery, and no dependency on
`search-debug`'s Elasticsearch `explain` internals — it only reads the query/filter/SKU state already on
the rendered SRP. Kept standalone so a shop can install it without either of those.

## Status

Feature-complete for its scope. Verified: 49 Codeception tests (Client, Zed, Zed GUI and Yves layers,
including real-database integration coverage for the full submit → reply → status-change → list/find round
trip), phpcs clean, and the package's public `SearchFeedbackFacade` at 100% method coverage. See
[Testing and CI](#testing-and-ci) below for the measured numbers and the (deliberate, documented) gaps.

## What it does

- **Yves**: a plain HTML form on the SRP (topic dropdown + free-text body), visible only to a customer
  holding `SubmitSearchFeedbackTicketPermissionPlugin`. Submitting redirects back to the SRP with a flash
  message — no AJAX, no new frontend component.
- **Zed**: a ticket grid + per-ticket conversation thread. Any Zed admin with access to the module can view
  every ticket and reply; changing a ticket's status (open/answered/closed) is its own controller action so
  it can be independently restricted to a "ticket worker" ACL group, leaving a "feedback admin" group able
  to view and reply but not triage. There is no per-submitter row-level scoping — Zed users and Yves
  customers are separate identity systems with no built-in link, so both Zed roles see the same full list.

The ticket grid (List of Tickets, sortable/searchable via DataTables) and a ticket's detail page (context +
full conversation thread + reply form + status actions):

![The Zed ticket grid: ID, topic, search term, a colored status badge (orange Open / green Closed), filed-at timestamp, and a View action per row](docs/screenshots/zed-ticket-list.png)

![The Zed ticket detail page: Ticket Context (topic, search term, filters JSON, page, SKUs shown, store/locale, status with Mark answered/Mark closed actions, filed-at), and Conversation showing the customer's original message and a Zed admin's reply, with a reply form below](docs/screenshots/zed-ticket-detail.png)

## Data model

- `spy_search_feedback_ticket` — topic, search term, filters (JSON), page number, SKU list (JSON), status,
  store/locale, and a plain `customer_reference` VARCHAR (deliberately not a real FK to `spy_customer` —
  same loose-coupling convention `search-ranking-optimizer` uses for its own rating table).
- `spy_search_feedback_ticket_message` — one row per message in the thread; `author_type` plus exactly one
  of `fk_user` (a Zed admin) or `customer_reference` (the original submitter) set per row.

## Installation

1. `composer require spryker-community/search-feedback`
2. Register `Pyz\Client\SearchFeedback\SearchFeedbackDependencyProvider`,
   `Pyz\Yves\SearchFeedbackWidget\SearchFeedbackWidgetDependencyProvider`,
   `Pyz\Zed\SearchFeedback\SearchFeedbackDependencyProvider`, and
   `Pyz\Zed\SearchFeedbackGui\SearchFeedbackGuiDependencyProvider` as project-level overrides of the
   package's own.
3. Register `SubmitSearchFeedbackTicketPermissionPlugin` in both the Client and Zed
   `PermissionDependencyProvider::getPermissionPlugins()`, and grant it to whichever company role should
   see the SRP ticket form (via `company_role_permission.csv` or the Company Role GUI).
4. Register `SearchFeedbackWidgetRouteProviderPlugin` in the Yves `RouterDependencyProvider` and
   `SearchFeedbackWidgetTwigPlugin` in the Yves `TwigDependencyProvider`.
5. Include the ticket-form view in your SRP template, gated on `canSubmitSearchFeedbackTicket()`.
   **It must render OUTSIDE any enclosing `<form>` your SRP template already has** (e.g. the catalog
   page's own filter/sort/pagination form). HTML doesn't allow nested forms — the browser silently drops
   the inner one, and clicking submit posts the OUTER form instead (wrong endpoint, wrong fields, no CSRF
   validation). Confirmed live: this exact breakage, when the include first landed inside that form.
6. Copy the `<search-feedback-gui>` block from this package's `Communication/navigation.xml` into your
   project's `config/Zed/navigation.xml`.
7. Run `console transfer:generate`, `console propel:diff` + `console propel:migrate` (not
   `propel:sql:insert` — that reapplies the full schema dump), and
   `console dev:ide-auto-completion:generate`.
8. Warm up the Zed **BackendGateway** router: `console router:cache:warm-up:backend-gateway`. It's a
   separate cache from the main Backoffice router's — every other page can work fine while ticket
   submission alone 404s (`No route found for "POST .../search-feedback/gateway/submit-ticket"`) until
   this runs. Same gotcha the sibling `search-ranking-optimizer` package's own Gateway controller has.
9. In the Zed ACL module, create your "ticket worker" and "feedback admin" groups and grant/deny access to
   `SearchFeedbackGui/Detail/changeStatus` accordingly — this package ships no ACL fixture data.

## Testing and CI

### Automated checks

`.github/workflows/ci.yml` runs on every push and pull request:

| check | what it protects |
|---|---|
| `composer validate` | the manifest stays well-formed |
| `phpcs` (PHP 8.3, 8.4) | coding standard, via this package's own `phpcs.xml` |
| `composer check-floors` (PHP 8.3, 8.4) | the declared dependency floors are real |
| `rector` dry-run (PHP 8.3, 8.4) | no unapplied Rector rule set drifts in |
| `phpmd` (`phpmd.xml` + `phpmd-public-methods.xml`) | cyclomatic/NPath complexity, method/class length stay reasonable — run as two separate invocations because PHPMD merges every loaded ruleset's `exclude-pattern` into one global file list per run, and only the public-method-count rule should skip Facades/Factories |

Same `check-floors` rationale as the sibling `search-debug`/`search-ranking` packages: this package's
`require` constraints are a promise about which Spryker versions an adopter may install, which a full demo
shop's dependency tree cannot itself verify. `composer check-floors` resolves every constraint to its
oldest allowed version and asserts every vendor symbol `src/` references still exists there.

### Test suite

The package ships Codeception suites under `tests/SprykerCommunityTest/`, one per layer, run **inside a
host shop** (they use the host's test bootstrap and, for the Zed suites, a live Propel/MySQL connection):

```bash
vendor/bin/codecept build -c vendor/spryker-community/search-feedback/tests/SprykerCommunityTest/Client/SearchFeedback
vendor/bin/codecept run   -c vendor/spryker-community/search-feedback/tests/SprykerCommunityTest/Client/SearchFeedback
vendor/bin/codecept run   -c vendor/spryker-community/search-feedback/tests/SprykerCommunityTest/Zed/SearchFeedback
vendor/bin/codecept run   -c vendor/spryker-community/search-feedback/tests/SprykerCommunityTest/Zed/SearchFeedbackGui
vendor/bin/codecept run   -c vendor/spryker-community/search-feedback/tests/SprykerCommunityTest/Yves/SearchFeedbackWidget
```

49 tests, all green, measured with `--coverage --coverage-text` (pcov):

| layer | tests | notable coverage |
|---|---|---|
| Client | 8 | `SearchFeedbackClient`, `SearchFeedbackFactory`, `SearchFeedbackStub`, `SearchFeedbackConfig`, permission plugin — 100% methods |
| Zed (`SearchFeedback`) | 31 | `SearchFeedbackFacade` 100% (5/5), `TicketManager` 100%, `SearchFeedbackEntityManager`/`Repository`/`Mapper` 100%, `CompanyUserPermissionAuthorizer` 100%, `GatewayController` 100% |
| Zed (`SearchFeedbackGui`) | 6 | `ReplyForm` validation (via a real Symfony `FormFactory`), `SearchFeedbackGuiCommunicationFactory` DI wiring |
| Yves (`SearchFeedbackWidget`) | 4 | `SearchFeedbackWidgetFactory` DI wiring |

The Zed suite's `GatewayControllerTest` and `SearchFeedbackFacadeTest` are real database integration
tests — no mocked Propel query builder — covering the full submit → reply → status-change →
list/find round trip through the actual Locator-resolved Facade, the same path `DetailController` and
`IndexController` drive in production. `CompanyUserPermissionAuthorizerTest` and `GatewayControllerTest`
together prove the authorization gate actually blocks unauthorized writes (persists nothing), not just
that it decorates the response — mirroring the equivalent tests in the sibling
`search-ranking-optimizer` package, which this module's own `CompanyUserPermissionAuthorizer` is a direct,
deliberate copy of.

### Known coverage gaps

Three classes are **not** exercised beyond a DI-wiring smoke test, and this is a structural limitation of
testing Communication/Yves-layer classes outside a live HTTP request — not an oversight:

- **`SearchFeedbackGuiCommunicationFactory::createReplyForm()`**. It resolves the real Zed Silex
  `form.factory` application service, which only exists once the full Zed app is bootstrapped — confirmed
  empirically (`Call to a member function create() on null` under Codeception's `Environment` helper
  alone). The `ReplyForm` type it builds has full, dedicated coverage in `ReplyFormTest` via a real,
  standalone Symfony `FormFactory` instead.
- **`TicketTable`**. Its `render()`/`fetchData()` resolve the request and Twig environment from the Zed
  application container the same way `getFormFactory()` does — same limitation, same reason no sibling
  package (`search-debug`, `search-ranking`, `search-ranking-optimizer`) tests a `Table` class either.
  Verified manually against a real ticket (see the screenshots above): status badge CSS class mapping and
  the per-row "View" action link both render correctly.
- **`SubmitTicketController`** (Yves). Reaches through `PermissionAwareTrait::can()` (Spryker's global
  `Locator` singleton, no constructor seam to substitute a fake permission client) and the flash-message
  helpers on `AbstractController`, both of which need a bootstrapped Yves Silex app — the exact same
  documented limitation the sibling `search-debug` package accepts for its own
  `SearchDebugContextEventDispatcherPlugin::handleRequest()` permission-granted branch. The controller's
  own request-parsing helper (`buildRedirectParameters()`) and its collaborators (`SearchFeedbackClient`,
  `SearchFeedbackWidgetFactory`) are covered independently.

Static analysis (`phpstan`, level 8, config in [`phpstan.neon`](phpstan.neon)) is likewise run from a host
shop rather than in CI: it needs the generated `Generated\Shared\Transfer\*` classes and the shop's
`Ide/AutoCompletion` stub freshly regenerated.

```bash
vendor/bin/console dev:ide-auto-completion:generate
vendor/bin/phpstan clear-result-cache -c vendor/spryker-community/search-feedback/phpstan.neon
vendor/bin/phpstan analyse -c vendor/spryker-community/search-feedback/phpstan.neon vendor/spryker-community/search-feedback/src
```

### Browser (Presentation) suite

> **This suite is a development tool for this package's own reference demoshop — it is not something
> to install or run against YOUR shop.** It logs in as a real Zed user and as
> `search-admin@test-company.example` (Yves, the one account this demoshop's fixtures grant
> `SubmitSearchFeedbackTicketPermissionPlugin` to), submits and replies to real tickets against this
> demoshop's seeded catalog and store/locale scope. Point it at a different shop and most of it will
> simply fail on missing data, not on a real defect. It exists to catch UI regressions while developing
> this package, not as something adopters are expected to run.

Two suites, split by layer:

- `tests/SprykerCommunityTest/Zed/SearchFeedbackGuiPresentation/` — the ticket grid, detail page (context
  table + conversation thread), reply-and-auto-transition rules (a reply moves an Open ticket to Answered,
  never moves an Answered/Closed one), manual status changes in either direction (self-contained: restores
  whatever status it found), reply-body escaping (a `<script>`/`&`/`<b>` payload asserted to render as
  literal text, never executes), edge cases (unknown status value, nonexistent ticket id on both the
  change-status and detail routes — all redirect gracefully, never crash), and a plain-catalog-search
  regression check confirming a logged-out guest sees none of this package's or its siblings' UI.
- `tests/SprykerCommunityTest/Yves/SearchFeedbackWidgetPresentation/` — the SRP ticket form: a real
  submission that redirects back with the success flash message, client-side rejection of a blank body,
  and the permission gate (anonymous guest, logged-in customer without the role, and the permitted
  customer as the positive control).

```bash
vendor/bin/codecept build -c packages/spryker-community/search-feedback/tests/SprykerCommunityTest/Zed/SearchFeedbackGuiPresentation
vendor/bin/codecept run   -c packages/spryker-community/search-feedback/tests/SprykerCommunityTest/Zed/SearchFeedbackGuiPresentation
vendor/bin/codecept build -c packages/spryker-community/search-feedback/tests/SprykerCommunityTest/Yves/SearchFeedbackWidgetPresentation
vendor/bin/codecept run   -c packages/spryker-community/search-feedback/tests/SprykerCommunityTest/Yves/SearchFeedbackWidgetPresentation
```

Like the rest of the test suite, neither is part of CI — both need a real running shop plus the Selenium/
chromedriver service already provisioned in this demoshop's `docker-compose.yml`.

## License

MIT — see [LICENSE](LICENSE).
