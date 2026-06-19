# Choose Test Scope

Use this skill when deciding whether a Zen Cart change should be covered by a core unit test, core feature test, or plugin-local test.

## Workflow

- use core unit tests for isolated logic under `not_for_release/testFramework/Unit/`
- use core feature tests for storefront or admin runtime behavior under `FeatureStore/` or `FeatureAdmin/`
- use plugin-local tests when the behavior belongs to one encapsulated plugin and should travel with that plugin
- when a test fails, classify whether the fix belongs in implementation, fixture/setup, or test expectation before adding coverage
- make plugin filesystem or installation-state tests `serial` and `plugin-filesystem` when they mutate runtime state
- prefer the smallest relevant composer script or PHPUnit filter instead of broad suite runs
- note when useful tests were not run or cannot run in the current environment

## Validation

- confirm the chosen test scope matches the changed behavior
- confirm runtime-sensitive tests are not misclassified as unit tests
- confirm filesystem-mutating plugin tests use the required isolation tags
