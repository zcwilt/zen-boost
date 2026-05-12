# Zen Boost

Zen Boost is an encapsulated Zen Cart plugin that starts a file-based developer tooling workflow for:

- caching selected `docs.zen-cart.com` pages locally
- building flat-file JSON catalogs for docs and repo code
- searching docs and code together without extra infrastructure
- validating plugin manifests against a baseline field set

## Positioning

Zen Boost is the Zen Cart analogue to Laravel Boost, but it is earlier in scope and maturity.

Both projects aim to make AI agents framework-aware by combining official framework guidance with project-local code inspection. The difference is that Laravel Boost already ships a first-class MCP server, agent guidelines, skills, and runtime inspection tools, while Zen Boost currently provides the retrieval and diagnostics foundation those higher-level integrations can build on.

Today, Zen Boost is best understood as:

- a Zen Cart-specific docs and repo indexing layer
- a local-first retrieval layer for agents and developer tooling
- a starting point for future MCP, guidance, and inspection features

It is not yet a full Laravel Boost equivalent in terms of agent integrations or runtime tool coverage.

## Current MVP Commands

From an installed Zen Cart checkout:

```bash
bin/zencart zen-boost:docs:fetch
bin/zencart zen-boost:catalog:build
bin/zencart zen-boost:docs:search manifest
bin/zencart zen-boost:docs:ask <question>
bin/zencart zen-boost:docs:compare <question>
bin/zencart zen-boost:manifest:inspect zc_plugins/zen-boost/v1.0.0/manifest.php
bin/zencart zen-boost:plugin:doctor zc_plugins/zen-boost/v1.0.0
bin/zencart zen-boost:make:plugin my-plugin
bin/zencart zen-boost:mcp:serve
```

## Storage Model

Zen Boost keeps its working data under the plugin:

- `resources/docs-cache/` for fetched page snapshots
- `resources/catalogs/docs-index.json` for chunked docs records
- `resources/catalogs/repo-index.json` for repo catalog records

No SQLite, Redis, or vector store is required.

## Current Capability Snapshot

Current implemented capabilities:

- fetch selected official Zen Cart documentation pages into a local cache
- chunk cached docs into a JSON docs index
- build a local repo catalog from high-value Zen Cart paths
- search docs and repo records together with simple weighted keyword ranking
- inspect plugin manifests for baseline required fields
- expose a local stdio MCP server with core read-only tools
- bundle Zen Cart guidance topics for agents
- provide a combined plugin doctor workflow
- expose a small admin diagnostics page for cache and catalog status

Not implemented yet:

- reusable task-specific skills

## Current MCP Slice

Zen Boost now includes a minimal stdio MCP server entrypoint:

```bash
bin/zencart zen-boost:mcp:serve
```

The plugin-local helper script at `bin/zen-boost-mcp` is a development shim for the standalone plugin repo, not the canonical launch path for an installed plugin. The stable entrypoint is the Zen Cart console command because the plugin itself lives in a versioned install directory.

When using the development shim from the standalone plugin repository, run it from the Zen Cart checkout root or set `ZEN_BOOST_PROJECT_ROOT` so repo inspection tools can resolve the target project correctly.

Client integration notes are in [docs/mcp-clients.md](docs/mcp-clients.md).

Current implemented MCP tools:

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
- `list_guidance_topics`
- `read_guidance_topic`
- `plugin_doctor`

Bundled guidance topics:

- `plugin-structure`
- `page-modules`
- `admin-pages`
- `security-output`

This is intentionally read-only and local-first. It uses the existing JSON catalogs, plugin inspection services, and bundled guidance files rather than introducing a separate storage or indexing layer.

`plugin_doctor` currently combines:

- manifest validation
- installer inspection
- installed-plugin state lookup
- filename lookup context
- admin page language checks
- catalog page header/language/template checks

## Planned MCP v1

The next major milestone is expanding the current MCP slice into a broader Zen Cart inspection surface.

Proposed `v1` tools:

- `search_docs`
- `search_repo`
- `compare_docs_to_code`
- `inspect_plugin_manifest`
- `inspect_plugin_installer`
- `inspect_bootstrap_loaders`
- `lookup_filename_constant`
- `list_page_modules`
- `list_installed_plugins`
- `read_recent_logs`
- `list_guidance_topics`
- `read_guidance_topic`
- `plugin_doctor`

Design constraints for `v1`:

- local-first and zero-extra-infrastructure
- read from the current Zen Cart checkout and Zen Boost JSON catalogs
- prefer official docs for conventions and local code for runtime truth
- return explicit mismatch notes when docs and code disagree

## Next Steps

- add reusable task-specific Zen Cart skills on top of the bundled guidance topics
- improve installed plugin inspection and DB-aware runtime reporting
- extend plugin doctor with deeper language, observer, and template-override checks
- improve docs-versus-code comparison quality
- improve HTML section chunking for the docs site
- add richer repo indexing for Zen Cart plugin conventions
