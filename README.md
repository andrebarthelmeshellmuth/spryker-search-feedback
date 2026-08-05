# Spryker Search Feedback

A voice-of-customer capture tool for the search results page (SRP): an authorized storefront admin can
file a free-text ticket about a set of search results — tied to the query term, active filters, page
number, and the SKUs actually shown on that page, plus a topic — and Zed back-office admins hold a
conversation about it from there. The Yves side is write-only: once a ticket is submitted there is no way
to read it, or any reply, back from the storefront. Everything past submission happens in Zed.

*Part of the [Search Relevance](https://search-relevance.dev/) project.*

## Why a separate package

Structurally close to [spryker-community/search-ranking-optimizer](https://github.com/andrebarthelmeshellmuth/spryker-search-ranking-optimizer)'s
SRP relevance-rating widget (Yves widget → Gateway-controller write → Zed persistence), but a different
bounded context: this is qualitative support/VoC ticketing, not a numeric ranking signal. It has no
dependency on `search-ranking`/`search-ranking-optimizer`'s tuning machinery, and no dependency on
`search-debug`'s Elasticsearch `explain` internals — it only reads the query/filter/SKU state already on
the rendered SRP. Kept standalone so a shop can install it without either of those.

## What it does

- **Yves**: a plain HTML form on the SRP (topic dropdown + free-text body), visible only to a customer
  holding `SubmitSearchFeedbackTicketPermissionPlugin`. Submitting redirects back to the SRP with a flash
  message — no AJAX, no new frontend component.
- **Zed**: a ticket grid + per-ticket conversation thread. Any Zed admin with access to the module can view
  every ticket and reply; changing a ticket's status (open/answered/closed) is its own controller action so
  it can be independently restricted to a "ticket worker" ACL group, leaving a "feedback admin" group able
  to view and reply but not triage. There is no per-submitter row-level scoping — Zed users and Yves
  customers are separate identity systems with no built-in link, so both Zed roles see the same full list.

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
6. Copy the `<search-feedback-gui>` block from this package's `Communication/navigation.xml` into your
   project's `config/Zed/navigation.xml`.
7. Run `console transfer:generate`, `console propel:diff` + `console propel:migrate` (not
   `propel:sql:insert` — that reapplies the full schema dump), and
   `console dev:ide-auto-completion:generate`.
8. In the Zed ACL module, create your "ticket worker" and "feedback admin" groups and grant/deny access to
   `SearchFeedbackGui/Detail/changeStatus` accordingly — this package ships no ACL fixture data.

## License

MIT — see [LICENSE](LICENSE).
