# Migrating to OpenSearch 3.x

Verified live end-to-end: a Spryker demoshop upgraded from **OpenSearch 1.3.4 to 3.5.0** (Lucene 10.3.2),
full re-export/reindex, `search-feedback:check-installation` re-run on 3.5.

**This package needs no code change for OpenSearch 3.x.** It issues no live search query. The only time it
touches the engine at all is reconstructing a previously-captured response for a ticket's frozen replay —
`Elastica\Response` / `Elastica\Query` / `Elastica\ResultSet\DefaultBuilder` rebuilt from data already
stored on the ticket, never a call to the cluster. That reconstruction path is engine-version-agnostic:
the `_search` response envelope (`hits.hits`, `_source`, `_score`, `total`) is unchanged across the
1.3.x → 3.5 range, and a snapshot captured on 1.3.4 replays identically on 3.5.

## The one thing to be aware of during the upgrade itself

Not this package's code, but you will hit it while upgrading the shop this package runs in: OpenSearch 3.x
bundles the neural-search plugin, whose `SemanticMappingTransformer` runs on **every index create** and
rejects a mapping that declares `"some-field": { "type": "object", "properties": {} }` with
`class java.util.ArrayList cannot be cast to class java.util.Map` (PHP's `json_decode` turns the empty
`{}` into `[]`, which Spryker then PUTs). Spryker Cloud Commerce fixed this in five core packages
(ticket SC-25160); a project schema override that makes `properties` non-empty (one inert
`{ "type": "boolean", "index": false }` field) works around any third-party schema still carrying it.
`spryker-community/search-ranking`'s own migration guide has the full write-up and the capability delta
between the two versions.
