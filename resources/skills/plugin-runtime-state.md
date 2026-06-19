# Plugin Runtime State

Use this skill when a plugin exists on disk but may not be installed, enabled, or loading from Plugin Manager state.

## Workflow

- separate filesystem presence from installed runtime state before debugging bootstrap discovery
- check Plugin Manager state first, because encapsulated plugins are only discovered after registration in `plugin_control`
- use the admin Plugin Manager UI for normal local enablement when possible
- use direct `plugin_control` and `plugin_control_versions` inserts only in tests or CI setup, never in application code
- confirm the installed version matches the versioned plugin directory being debugged
- inspect runtime loader expectations only after installed state is confirmed

## Validation

- confirm the plugin root exists on disk
- confirm installed state was checked before blaming bootstrap wiring
- confirm any direct DB enablement is limited to test or CI setup
