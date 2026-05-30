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

## Quality gates (when scaffold exists)

```bash
composer check
npm run check --prefix frontend
```

Last updated: 2026-05-30
