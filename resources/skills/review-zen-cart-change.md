# Review Zen Cart Change

Use this skill when reviewing a Zen Cart patch or pull request as a maintainer rather than as a generic style checker.

## Workflow

- lead with material findings: bugs, regressions, compatibility risks, deployment risks, and missing tests
- use `CONVENTIONS.md` and `AGENTS.md` as the review baseline, not broad cleanup preferences
- check whether the change edits bootstrap or path files that should normally be extended through plugin hooks, `extra_configures`, or `init_includes`
- review plugin changes against encapsulated layout, installer conventions, and runtime enablement expectations
- mention test gaps and commands not run when confidence depends on them

## Validation

- confirm findings are ordered by severity
- confirm file and line references are included where practical
- confirm the review distinguishes material defects from optional cleanup
