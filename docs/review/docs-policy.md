# Docs Policy Checklist

Use before merging documentation PRs.

- [ ] Scope matches `docs/explanation/scope-contract.md` — pipeline only
- [ ] No Clear-owned features documented as Deal responsibilities (reconciliation, dunning, bank CSV)
- [ ] Invoice handoff references HTTP + link ids, not duplicated billing logic
- [ ] English in repo Markdown (Issue/PR bodies may be Japanese)
- [ ] ADR added if boundary or integration decision changed
- [ ] Locale policy respected — product UI strings target `ja`/`en` only (ADR 0004)
- [ ] Current work log (private mirror `nene-origin/internal-docs/deal/todo/current.md`) updated when phase milestones shift

Last updated: 2026-05-30
