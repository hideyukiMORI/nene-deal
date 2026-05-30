# ADR 0001: Inherit NENE2 Governance

## Status

Accepted

## Context

NeNe Deal is a consumer product on NENE2. Reinventing workflow, HTTP patterns, and
quality gates would drift from the family.

## Decision

- Use NENE2 as framework dependency (`hideyukimori/nene2`).
- Inherit Issue-driven workflow, Conventional Commits, PHPStan 8, OpenAPI-first API.
- Document Deal-specific boundaries locally; link NENE2 for runtime patterns.

## Consequences

- Faster bootstrap; consistent operator experience across NeNe products.
- Must track NENE2 semver for breaking changes.

## Related

- `docs/inheritance-from-nene2.md`
