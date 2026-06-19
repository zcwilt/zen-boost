# Security Review

Use this skill when a Zen Cart change touches request handling, output, admin inputs, redirects, files, downloads, uploads, or webhook-like listeners.

## Workflow

- preserve the early request-sanitizing behavior in `includes/application_top.php`
- check whether user-controlled output is escaped with `zen_output_string_protected()`
- avoid spreading raw `$_GET`, `$_POST`, or `$_REQUEST` access when established helpers exist
- require documented admin sanitization whitelisting before relaxing admin-side sanitization
- inspect file paths, redirects, download handlers, uploads, and listener endpoints for traversal or trust-boundary mistakes
- prefer existing Zen Cart helpers for URLs and output instead of hand-built HTML or query strings

## Validation

- confirm user-controlled output is protected
- confirm admin sanitization changes are explicit and documented
- confirm file or redirect handling does not widen the trust boundary unexpectedly
