# Debug Test Failure

Use this skill when a core or plugin-local Zen Cart test is failing and the next step is to isolate the failure mode instead of changing production code blindly.

## Workflow

- identify whether the failure is unit, storefront feature, admin feature, or plugin-local
- rerun the smallest relevant test command with a focused filter
- read the first real failure before following cascading errors
- decide whether the defect is in production code, test expectations, or test/setup state
- check whether the failure is caused by runtime bootstrap, DB config, grouping/isolation, or the assertion itself
- inspect recent test artifacts and logs when the failure is in-process or runtime-sensitive
- separate flaky environment/setup failures from real behavior regressions before editing application code

## Common Zen Cart Causes

- plugin is not installed or enabled in test setup
- plugin filesystem layout under `zc_plugins/<unique_key>/<version>/...` is wrong
- runtime code assumes Composer autoloading instead of Zen Cart bootstrap autoloading
- required `FILENAME_*` or table-name constants are missing
- storefront or admin output changed and the assertion no longer matches intended behavior
- test fixtures or DB state no longer match bootstrap assumptions

## Validation

- confirm the failure is reproducible with the smallest relevant command
- confirm the suspected cause is classified as code behavior, runtime setup, or test-isolation issue
- confirm the chosen fix targets the cause, not just the symptom
