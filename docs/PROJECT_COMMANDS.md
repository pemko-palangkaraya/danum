# DANUM Project Command System

> Project-specific hashtag commands for consistent repository work.

## Purpose

These commands define not only **what** to do, but also the required **thinking order before taking action**. Commands must be interpreted in the context of the current DANUM repository state.

## Core Rule

Before making changes, inspect the relevant repository state, existing implementation, project documentation, and conventions. Avoid unnecessary changes outside the requested scope.

For example, `#audit_besar` means:

`repository → docs → configuration/structure → history → implementation → compare with project goals → identify gaps → classify issues → prioritize → report`

`#mulai` means project orientation first, not immediate coding.

---

## Lifecycle

- `#mulai` — Check the initial project condition: repository structure, `/docs`, main README, latest commit, Git branch/status, uncommitted changes, latest implementation state, and unfinished work. Produce a **Project State Summary**. Do not modify code before understanding the current state.
- `#lanjut` — Continue the latest work from the current project state. Read recent context and changes before acting.
- `#status` — Give a concise snapshot: completed, in progress, pending, problems, and next step.
- `#checkpoint` — Record the current project state so future work can resume with clear context.
- `#selesai` — Evaluate the completed work, verify changes, documentation, tests, and readiness for commit.

## Audit & Evaluation

- `#audit_besar` — Comprehensive audit of project goals/scope, architecture, repository structure, implementation, dependencies, documentation, code quality, consistency, technical debt, risks, unfinished work, and priorities. Output: **Project Health Report + Work Priorities**.
- `#audit_kode` — Audit code quality, structure, maintainability, potential bugs, duplication, and problematic patterns.
- `#audit_arsitektur` — Audit architecture/design and alignment between implementation and project goals.
- `#audit_docs` — Audit `/docs`, README, specifications, decision records, and documentation/implementation alignment.
- `#audit_git` — Audit Git history, latest commits, branches, uncommitted changes, commit patterns, and repository risks.
- `#audit_keamanan` — Review project security and identify potential vulnerabilities or misconfigurations.
- `#audit_final` — Final audit before release, merge, or deployment.

## Planning

- `#rencana` — Analyze the current project state and create a work plan for the next task.
- `#pecah_task` — Break a large task into concrete, ordered smaller tasks.
- `#prioritas` — Prioritize work using impact, dependencies, risk, and project condition.
- `#next` — Determine the most appropriate next step from the current repository state.
- `#roadmap` — Review the roadmap and define the next milestones.

## Development

- `#kerjakan` — Implement the discussed task after understanding existing project structure and conventions.
- `#debug` — Investigate systematically: reproduce → trace → root cause → fix → verify.
- `#fix` — Fix a specific bug/problem without unnecessary out-of-scope changes.
- `#refactor` — Refactor while preserving existing behavior and minimizing unrelated changes.
- `#test` — Review, create, or run relevant tests for the latest change.
- `#verify` — Verify that the implementation works and has not broken related functionality.

## Documentation

- `#docs` — Check and update documentation affected by the latest changes.
- `#catat` — Document decisions, changes, findings, or important notes in the appropriate documentation location.
- `#decision` — Create an Architecture/Technical Decision record for a newly made decision.
- `#sync_docs` — Compare documentation with the actual implementation and identify discrepancies.

## Git / Repository

- `#git` — Analyze the current Git repository state.
- `#commit` — Review changes and help determine an appropriate commit and commit message.
- `#review_commit` — Review the latest commit for purpose, correctness, and potential issues.
- `#diff` — Analyze the latest changes/diff comprehensively.
- `#clean` — Check whether the repository is clean and identify unresolved changes.

## Quality Review

- `#review` — General review of the current work or implementation.
- `#review_besar` — Deep review of a specific change or project area.
- `#cek_scope` — Check whether implementation remains within scope or has expanded unnecessarily.
- `#cek_konsistensi` — Find inconsistencies between code, architecture, configuration, documentation, and project conventions.
- `#cek_regresi` — Look for regressions caused by recent changes.

## Meta Commands

- `#jelaskan` — Explain the relevant project area clearly while retaining technical accuracy.
- `#temukan` — Locate implementation, configuration, documentation, or references related to a subject.
- `#bandingkan` — Compare two approaches/implementations and recommend one.
- `#analisis` — Perform deeper analysis before taking action.
- `#hentikan` — Do not make changes; only analyze and report the condition/risks.
- `#aman` — Before a major change, identify risks and the safest implementation approach.
- `#paksa` — Proceed despite warnings or imperfections, but report the risks first.

## Core 10 Commands

| Command | Purpose |
|---|---|
| `#mulai` | Understand and assess repository state |
| `#status` | Snapshot current project state |
| `#lanjut` | Continue the latest work |
| `#audit_besar` | Comprehensive project audit |
| `#audit_kode` | Code-quality audit |
| `#rencana` | Create a work plan |
| `#kerjakan` | Execute the work |
| `#debug` | Investigate and fix a problem |
| `#review` | Review completed/current work |
| `#selesai` | Final check of the work |

## DANUM Working Convention

1. Work directly on `master` unless the user explicitly requests another branch.
2. Inspect existing implementation before creating new foundations.
3. Prefer readable, effective, maintainable code.
4. Keep changes focused and avoid unnecessary scope expansion.
5. If obsolete code is clearly unused and only appears to exist for testing, it may be removed after checking its references.
6. Testing should verify both the requested feature and relevant regression risks.
7. Keep `/docs` synchronized with meaningful architectural, implementation, or workflow changes.
8. When the user explicitly invokes a command, follow that command's procedure before taking action.
