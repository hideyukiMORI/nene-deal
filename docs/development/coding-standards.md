# Coding Standards

NeNe Deal follows **NENE2 consumer** standards. Authoritative runtime rules live in
NENE2 `docs/development/coding-standards.md` after `composer install`.

## Local additions

| Topic | Rule |
| --- | --- |
| Scope | Handlers thin; UseCase owns stage rules and handoff orchestration |
| Money | Integer minor units on deal cards; no float amounts in DB |
| Links | Store Invoice ids as opaque strings; no cached quote JSON |
| Boundaries | No Clear/reconciliation code paths in this repo |
| i18n | UI strings in `ja`/`en` message catalogs only ([ADR 0004](../adr/0004-bilingual-ja-en-only.md)); no hardcoded display text |

## Quality gates (when scaffold exists)

```bash
composer check
npm run check --prefix frontend
npm run audit --prefix frontend
```

Dependency advisories are gated by `audit-ci`; the policy and the current exceptions live in
[`dependency-audit.md`](./dependency-audit.md).

Last updated: 2026-05-30
