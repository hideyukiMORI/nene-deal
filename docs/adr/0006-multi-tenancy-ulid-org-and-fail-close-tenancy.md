# ADR 0006: Multi-Tenancy — ULID Organization PK, Claim-First Resolution, Fail-Close Tenancy Package

## Status

Accepted (documents decisions shipped in #22, #32, #69; written down for #107 /
audit #91 (j))

## Context

Every data row in Deal belongs to exactly one organization
(`organization_id` on `users`, `pipeline_stages`, `deals`, `audit_events`).
The 2026-07-11 structural audit found the design itself sound — Deal's
`src/Tenancy/` was rated the most product-agnostic tenant seam in the fleet
and the best seed for upstreaming a scoped-repository pattern into NENE2 —
but none of its decisions were written down, and one of them (the ULID
organization PK) deliberately diverges from the other three products
(auto-increment integers). An undocumented divergence looks like an accident;
this ADR records why it is not.

## Decision

### 1. Organization PK is a ULID string(26), not an auto-increment integer

`organizations.id` (and every `organization_id` FK, and the other domain PKs:
users, deals, stages, history rows) is a **ULID stored as `string(26)`**
(`database/migrations/20260530120000_create_organizations_table.php`).

Reasons:

- **Non-enumerable tenant handles.** Org ids travel in JWT claims (`org`) and
  appear in URLs/exports. Sequential integers leak tenant count and invite
  IDOR guessing; ULIDs don't.
- **Client- or provisioner-side id minting.** The disposable-demo provisioner
  (#69) and the installer create org + admin + stages in one pass; ULIDs are
  minted before INSERT with no round-trip for `lastInsertId()`, and the same
  pattern holds for every aggregate (deals are created id-first too).
- **Merge/import safety.** Demo orgs, seed orgs and future imported tenants
  can coexist without id collisions.
- **Sortable randomness.** ULIDs are time-ordered, so `ORDER BY id` stays
  index-friendly (unlike UUIDv4).

Trade-offs accepted: 26-byte keys instead of 8-byte ints (negligible at
Deal's scale) and divergence from invoice/clear/vault. If the fleet
standardizes tenant PKs upstream, ULID-string is the shape Deal proposes —
int PKs cannot adopt the properties above retroactively, while ULID PKs lose
nothing. One knock-on effect is already visible: `Nene2\Audit` and
`Nene2\Demo` both accept `string|int` ids specifically so ULID products fit
(deal registers per-process int handles via `DemoOrgHandles` where the
framework insists on ints).

### 2. Request organization resolution: signed claim → header → sole-org

`RequestOrganizationMiddleware` resolves the tenant once per request, in this
order:

1. **Verified bearer `org` claim** (authoritative). Users belong to exactly
   one organization, so the signed claim wins and cannot be spoofed by a
   header. This is also what locks a disposable-demo session (#69) to its own
   throwaway org. A claim pointing at a vanished org (e.g. reaped demo) is a
   404, not a fallback.
2. **`X-Organization-Slug` header** — pre-auth / machine traffic; unknown
   slug is a 404.
3. **Sole-org convenience** — when exactly one organization is provisioned,
   header-less requests resolve to it (single-tenant install ergonomics).
   With the demo enabled orgs are plural, so authenticated traffic resolves
   via the claim instead.

When nothing resolves, the holder is **left unset** — infra routes
(`/health`) still work, and anything touching tenant data fails closed
downstream (see 3).

### 3. `Tenancy/` package is fail-close and product-agnostic

- `CurrentOrganization` (interface: `id(): string`) is the only thing
  repositories see.
- `HolderCurrentOrganization` reads the request-scoped holder and **throws
  when unresolved** — a repository can never silently query across tenants or
  with an empty scope. This is the fleet's only tenancy seam that fails
  closed by construction rather than by handler discipline.
- `FixedOrganization` gives tests and fixed-tenant tools a deterministic
  scope.
- Every repository takes `CurrentOrganization` via constructor and scopes SQL
  with it; none read headers, claims or globals.

This seam is the upstreaming candidate: `CurrentOrganization` +
`HolderCurrentOrganization` + `FixedOrganization` contain nothing
Deal-specific and could move to NENE2 as the scoped-repo contract
(workspace-layer tracking).

## Consequences

- Cross-tenant access requires a *signed* claim for the target org; there is
  no header or parameter that overrides it.
- There is deliberately no superadmin/org-null layer yet; introducing one
  (audit #91 (f)) must extend the resolution rules above, not bypass the
  holder — see the design issue for that item.
- Identity lookups (`findById` / `findByEmail` on users) intentionally run
  unscoped: they execute on pre-auth paths (login, token resolution) before a
  tenant exists. They are documented as such on `UserRepositoryInterface`.
- `audit_events.organization_id` is a nullable ULID string for the one event
  class that must not carry a tenant (failed logins — ADR 0005).
