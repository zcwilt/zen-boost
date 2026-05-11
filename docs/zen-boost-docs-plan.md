# Zen Boost Documentation Ingestion Plan

## Relation to Laravel Boost

Zen Boost is intended to fill the same role for Zen Cart that Laravel Boost fills for Laravel: make AI-assisted development materially better by giving agents framework-specific context, conventions, and tooling.

The intended parity is architectural, not literal.

Shared direction:

- combine official framework guidance with project-local code inspection
- make agents prefer framework conventions instead of generic PHP patterns
- expose a repeatable local interface for editor and CLI integrations

Current gap versus Laravel Boost:

- Laravel Boost already ships a mature MCP surface, agent guidelines, package-aware skills, and runtime inspection tools
- Zen Boost currently implements the retrieval and catalog layer, plus a small amount of diagnostics and manifest validation
- Zen Boost does not yet provide a first-class agent protocol layer or runtime introspection tools

That means Zen Boost should be planned in two stages:

1. complete the retrieval and diagnostics foundation
2. expose that foundation through a Zen Cart-specific MCP server, guidance files, and skills

The first MCP slice is now narrow but real:

- stdio transport
- tool discovery
- docs search
- repo search
- docs-versus-code comparison
- manifest inspection
- installer inspection
- bootstrap loader inspection
- filename constant lookup
- page module discovery
- recent log inspection
- installed plugin state inspection
- bundled guidance topic access
- combined plugin doctor workflow

The official launch path for an installed plugin should be the shared Zen Cart console:

- `bin/zencart zen-boost:mcp:serve`

The plugin-local `bin/` script can still exist as a development shim in the standalone plugin repository, but it should not be treated as the stable integration contract because installed plugins live in versioned directories.

That is enough to validate the transport shape and service boundaries before adding deeper runtime inspection.

## Implementation Form

`zen-boost` should be implemented as an encapsulated Zen Cart plugin under:

- `zc_plugins/zen-boost/v1.0.0/`

The plugin should own the Zen Cart-specific integration points, cached documentation snapshots, generated JSON catalogs, diagnostics, and developer-facing tools.

An MCP server should be exposed from the same plugin for local development use in editors such as VS Code. That MCP server should read from the plugin's flat-file docs and code catalogs instead of depending on separate infrastructure.

## Goal

Create a docs-aware developer tooling layer for Zen Cart that can use official documentation from `docs.zen-cart.com` together with local repository code to answer questions, generate scaffolds, inspect the current application state, and validate plugin implementations, without requiring extra infrastructure such as SQLite, Redis, or a vector store.

## Core Direction

The system should treat Zen Cart's official documentation as the primary source for public guidance and conventions, while treating the local codebase as the primary source for actual runtime behavior.

When documentation and code differ, the tooling should surface the mismatch instead of silently picking one.

The long-term outcome should be that an agent using Zen Boost behaves more like an experienced Zen Cart plugin developer than a generic PHP coding assistant.

## Initial Scope

Start by ingesting a small set of high-value developer documentation sections:

- `https://docs.zen-cart.com/dev/`
- `https://docs.zen-cart.com/dev/plugins/`
- `https://docs.zen-cart.com/dev/plugins/encapsulated_plugins/`
- `https://docs.zen-cart.com/dev/plugins/encapsulated_plugins/manifests/`
- `https://docs.zen-cart.com/dev/schema/`

These sections are enough to support plugin development, manifest validation, and developer guidance for an initial MVP.

## Architecture

### Plugin Layout

The first implementation should live inside a plugin structure similar to:

- `zc_plugins/zen-boost/v1.0.0/manifest.php`
- `zc_plugins/zen-boost/v1.0.0/Installer/ScriptedInstaller.php`
- `zc_plugins/zen-boost/v1.0.0/catalog/includes/classes/`
- `zc_plugins/zen-boost/v1.0.0/admin/includes/classes/`
- `zc_plugins/zen-boost/v1.0.0/admin/`
- `zc_plugins/zen-boost/v1.0.0/resources/docs-cache/`
- `zc_plugins/zen-boost/v1.0.0/resources/catalogs/`
- `zc_plugins/zen-boost/v1.0.0/bin/`

Suggested responsibilities:

- `resources/docs-cache/` stores fetched documentation snapshots
- `resources/catalogs/` stores generated JSON chunk and repo catalogs
- `admin/` exposes diagnostics or developer tools pages
- `bin/` contains local developer scripts such as catalog rebuild helpers or an MCP entrypoint

### 1. Documentation Ingestion

Build a crawler or fetch pipeline that stores:

- page URL
- page title
- heading structure
- normalized body text
- topic tags
- fetch date
- last-modified date when available

The fetch layer should cache raw page content locally as flat files so repeated indexing runs do not need to re-download unchanged pages.

### 2. Documentation Chunking

Chunk documentation by heading section instead of fixed-size token windows.

Each chunk should retain:

- source URL
- heading path
- short excerpt
- topic tags such as `plugin`, `manifest`, `installer`, `schema`, `language-files`
- version hints such as `1.5.8`, `2.2`, or `3.0` when detected

This should make retrieval more accurate for procedural docs while keeping the data simple enough to search from flat files.

### 3. Repository Indexing

Build a lightweight searchable catalog from the local repository, with priority on:

- `zc_plugins/*/*/manifest.php`
- plugin installer classes
- `includes/application_top.php`
- `includes/classes/`
- `includes/init_includes/`
- local Markdown plans and developer docs under `docs/`

This catalog should extract:

- file path
- class names
- function names
- selected comments or docblocks
- neighboring code snippets for retrieval context

The catalog can be written as JSON files generated into a local cache directory inside the project or plugin.

### 4. Retrieval Layer

For a given developer question:

1. classify the query as docs-only, code-only, or mixed
2. retrieve relevant documentation chunks
3. retrieve relevant local code or repo docs
4. merge the evidence into one grounded result

The retrieval layer should prefer:

- official docs for intended conventions
- local code for actual implementation details

The retrieval layer is the shared foundation for both CLI commands and MCP tools. It should not be coupled to one interface.

### 5. Answer Layer

Responses produced by the tooling should include three parts:

- documented approach
- current repo behavior
- mismatch or confidence note

This is especially important for areas where docs may lag the codebase.

When appropriate, responses should also include:

- concrete file references in the local repository
- source URLs for documentation evidence
- an explicit recommendation about whether the user should follow docs, code, or both

## Storage Strategy

For the MVP, use a zero-extra-infrastructure local-first design:

- cached raw pages as local files
- chunk records stored as JSON
- repository symbol and snippet records stored as JSON
- plain text, keyword, and heading-aware ranking

Do not require SQLite, Redis, embeddings, or a hosted search service for the first version.

If search quality later proves insufficient, a hosted documentation API can be considered as a second-phase enhancement.

## CLI Surface

The first useful interface should be a CLI inside a `zen-boost` package or plugin.

Suggested commands:

- `docs:search <term>`
- `docs:ask "<question>"`
- `docs:compare "<question>"`
- `plugin:doctor <plugin>`
- `make:plugin <name>`

Examples of intended use:

- search manifest fields and requirements
- explain how plugin installers are discovered
- compare manifest docs to a local plugin manifest
- diagnose why a plugin is not loading

The first version of `docs:search` should use:

- exact term matching
- heading matches
- tag matches
- simple weighted keyword ranking

This should be enough for Zen Cart's documentation size without introducing semantic search infrastructure.

## MCP Server v1

The plugin should expose an MCP server for local development once the retrieval layer is stable.

The `v1` MCP server should:

- run from the local project
- read the plugin's docs snapshots and JSON catalogs
- search the local repository in addition to docs snapshots
- expose Zen Cart-specific tools to editors or agents
- remain read-heavy and low-risk at first

Proposed `v1` tools:

- `search_docs`
- `search_repo`
- `compare_docs_to_code`
- `inspect_plugin_manifest`
- `inspect_plugin_installer`
- `list_installed_plugins`
- `inspect_bootstrap_loaders`
- `lookup_filename_constant`
- `list_page_modules`
- `read_recent_logs`

Suggested tool contracts:

- `search_docs`
  - input: free-text query, optional tag filters, optional result limit
  - output: ranked docs chunks with URL, heading path, excerpt, and score
- `search_repo`
  - input: free-text query, optional path scope, optional result limit
  - output: ranked repo records with file path, symbols, excerpt, and score
- `compare_docs_to_code`
  - input: free-text query
  - output: docs evidence, code evidence, and mismatch/confidence note
- `inspect_plugin_manifest`
  - input: manifest path
  - output: parsed manifest, missing fields, and validation findings
- `inspect_plugin_installer`
  - input: plugin root or installer class path
  - output: detected installer files, likely install hooks, and missing pieces
- `list_installed_plugins`
  - input: optional status filter
  - output: installed plugin records derived from the current application state
- `inspect_bootstrap_loaders`
  - input: none or optional subsystem scope
  - output: discovered autoload/init loader files and relevant plugin loader inputs
- `lookup_filename_constant`
  - input: filename constant or page basename
  - output: definition locations and matching entrypoints
- `list_page_modules`
  - input: page name
  - output: matching `includes/modules/pages/<page>/` files and template candidates
- `read_recent_logs`
  - input: optional filename pattern and line limit
  - output: recent log excerpts suitable for troubleshooting

This keeps the editor integration lightweight. The editor talks to the local MCP server, and the MCP server talks only to local files plus the current Zen Cart codebase.

Implemented in the current slice:

- `search_docs`
- `search_repo`
- `compare_docs_to_code`
- `inspect_plugin_manifest`
- `inspect_plugin_installer`
- `inspect_bootstrap_loaders`
- `lookup_filename_constant`
- `list_page_modules`
- `read_recent_logs`
- `list_installed_plugins`

Still pending for broader `v1`:

## AI Guidance and Skills

To close the biggest gap with Laravel Boost, Zen Boost should provide up-front agent guidance in addition to retrieval tools.

Required guidance areas:

- storefront page/module structure
- encapsulated plugin structure and manifest rules
- installer and upgrade script expectations
- admin page conventions
- security expectations for output escaping and input sanitization
- bootstrap and autoloading constraints

These should be delivered in two layers:

- always-loaded guidance for core Zen Cart conventions
- optional skills for focused tasks such as plugin creation, admin page creation, manifest validation, and troubleshooting plugin bootstrap issues

The goal is to reduce generic PHP output and push agents toward idiomatic Zen Cart implementations before they begin editing code.

The first implementation can stay simple:

- store curated guidance as local Markdown topics inside the plugin
- expose those topics through MCP tools so agents can request them directly
- add higher-level task-specific skills only after the base guidance set is stable

The first workflow built on top of that base should be `plugin:doctor`, which combines:

- manifest validation
- installer inspection
- installed-plugin state lookup
- filename and bootstrap-adjacent checks
- admin page language checks
- catalog page header, language, and template checks

## Runtime Inspection Layer

Laravel Boost is valuable partly because it can inspect the live application, not just read documentation.

Zen Boost should add Zen Cart-specific inspection features with priority on:

- installed plugin state via `plugin_control`
- plugin version and manifest discovery
- bootstrap and autoloader inputs
- filename constant locations
- page/module discovery
- configured filesystem and web paths
- recent logs and last-failure diagnostics

These tools would give Zen Boost practical debugging value beyond search and retrieval.

## Guardrails

- Prefer official Zen Cart docs over forum discussions for baseline guidance.
- Prefer local repository code over docs when runtime behavior disagrees with the docs.
- Always keep source URLs attached to indexed documentation records.
- Store fetch dates so the snapshot's freshness is visible.
- Reindex incrementally where possible instead of fully crawling every run.
- Keep the search implementation understandable and file-based for the first release.
- Start the MCP surface with read-only and inspection-oriented tools before adding any write or execution capabilities.
- Avoid promising Laravel Boost feature parity until MCP, guidance, and runtime inspection are actually implemented.

## MVP Implementation Order

1. Build a fetcher for the selected `docs.zen-cart.com` sections.
2. Store raw page snapshots and metadata locally.
3. Chunk the docs by heading and tag the chunks.
4. Write chunk and repo catalogs as local JSON files.
5. Implement a combined retrieval command for docs plus code using keyword and heading-aware search.
6. Add a simple comparison mode that reports docs/code mismatches.
7. Expand the MCP server from the current core slice into a broader runtime inspection surface.
8. Add core AI guidance and Zen Cart-focused skills.
9. Add read-only runtime inspection tools for plugins, bootstrap, paths, and logs.
10. Add developer-facing commands such as `plugin:doctor` and `make:plugin`.

## Best First Deliverable

The best first deliverable was a local CLI that can answer questions such as:

- what fields belong in a plugin manifest
- how plugin classes are loaded
- how an installer should be structured
- whether a local plugin matches the documented conventions

That proves the value of documentation ingestion before investing in a larger assistant, IDE integration layer, or hosted search service.

The next best deliverable is expanding the current MCP surface with runtime inspection tools without changing the storage model.

## What Not To Do

- Do not try to ingest the entire Zen Cart ecosystem at once.
- Do not rely on documentation alone when local code can be inspected.
- Do not hide docs/code disagreements from the user.
- Do not introduce a large infrastructure dependency for the first version.
- Do not over-engineer search before validating that basic keyword retrieval is insufficient.

## Summary

The best approach is a dual-source documentation system:

- ingest official `docs.zen-cart.com` pages for conventions and guidance
- write flat-file JSON catalogs for docs and local code
- retrieve from both and present the result together

That foundation should then be extended with:

- an MCP server for agent interoperability
- AI guidance and skills for Zen Cart conventions
- runtime inspection tools for real troubleshooting

That is the practical path for Zen Boost to become the Zen Cart counterpart to Laravel Boost instead of only a documentation indexing project.
