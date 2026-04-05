---
name: ci-workflow-update
description: Workflow command scaffold for ci-workflow-update in Chirper.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /ci-workflow-update

Use this workflow when working on **ci-workflow-update** in `Chirper`.

## Goal

Update continuous integration (CI) workflows for deployment or testing automation.

## Common Files

- `.github/workflows/deploy.yaml`
- `.github/workflows/test.yaml`
- `docker/app/Dockerfile.prod`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Edit or optimize YAML files in .github/workflows/
- Update Dockerfile or related deployment configuration if needed
- Commit changes with 'ci:' prefix in message

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.