# Inheritance from NENE2

NeNe Deal is a **NENE2 consumer product** (same pattern as NeNe Invoice, NeNe Records).

| Layer | Repository |
| --- | --- |
| Framework | [NENE2](https://github.com/hideyukiMORI/NENE2) |
| Product | **nene-deal** (this) |

## Inherited by policy

| Topic | Local document |
| --- | --- |
| Workflow | `docs/workflow.md` |
| Commits | `docs/development/commit-conventions.md` |
| Coding index | `docs/development/coding-standards.md` |
| ADRs | `docs/adr/` |

## Inherited by reference

Follow NENE2 upstream unless an ADR in this repo says otherwise:

- HTTP runtime, middleware, Problem Details
- Handler → UseCase → Repository layout
- Phinx migrations, PHPUnit, PHPStan 8
- OpenAPI-first public API

See `vendor/hideyukimori/nene2/docs/` after `composer install`.

## Deal-specific (local SSOT)

| Topic | Document |
| --- | --- |
| Pipeline scope | `docs/explanation/scope-contract.md` |
| Invoice handoff | `docs/integrations/invoice-handoff-contract.md` |
| Not billing SSOT | ADR 0002 |

## Problem Details base URL

`https://nene-deal.dev/problems/{slug}` (application errors)

Last updated: 2026-05-30
