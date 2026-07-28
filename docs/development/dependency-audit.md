# Dependency vulnerability gate (frontend)

Every PR runs a dependency audit as a **merge gate**. This document says what the gate is,
how an exception is granted, and what is currently excepted.

- Config: [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) (the file itself carries
  the reasoning for each entry — keep the two in sync)
- Command: `npm run audit --prefix frontend`
- CI: the `Audit (fail on high/critical)` step of `Frontend CI`

## The gate

`audit-ci` fails the build on any **high** or **critical** advisory that is not explicitly
allowlisted. Moderate and below do not fail (they are still reported).

We use `audit-ci` rather than bare `npm audit --audit-level=high` for one reason: **`npm audit`
has no way to record a reasoned exception.** Without one, the only ways past a
not-yet-fixable advisory are to lower the severity threshold or drop the step — both of which
blind the gate to *everything*, not just the advisory in question.

Measured when the gate landed (2026-07-29): removing the single allowlist entry makes the
command exit `1`; with it, exit `0`. The gate is not decorative.

## Rules for an exception

1. **Per advisory id, never per severity.** Allowlist `GHSA-…`; do not raise `--audit-level`
   and do not set `high: false`. A new advisory must still fail the build the day it lands.
2. **The reason must be measured, not assumed.** State why the vulnerable code path does not
   exist *in this codebase*, and how that was checked (a grep, a build artifact, a config).
   "We probably don't use that" is not a reason.
3. **Every entry has an expiry** and a named condition that removes it (an upgrade wave, an
   upstream fix). An expired entry is a task — re-argue it in a PR; do not extend it by reflex.
4. **Prefer the fix.** If a patched version exists in a range we can take, take it. An
   exception is only for "no fix exists that we can adopt".

Rule 4 did most of the work here. Of the **11** advisories present when this landed, **8 were
removed by upgrading**:

| Change | Advisories removed |
| --- | --- |
| `brace-expansion@5` → `^5.0.8` (replacing three per-major pins) | `GHSA-mh99-v99m-4gvg` for every copy that can take v5 |
| `postcss` override → `^8.5.24` | `GHSA-r28c-9q8g-f849` (path traversal via `sourceMappingURL`) |
| `react-router-dom` `^7.9.6` → `^7.18.1` | `GHSA-chx6-hx7r-mcp5` (high, DoS via route matching) + `GHSA-wrjc-x8rr-h8h6`, `GHSA-h8fp-f39c-q6mh`, `GHSA-337j-9hxr-rhxg` (moderate) |

The remaining `low` is `esbuild` `GHSA-g7r4-m6w7-qqqr`, a dev-server read on Windows — below
the gate, and not a path this repo runs.

### An override is a code change, not a version number

The first attempt at the `brace-expansion` fix was a **flat** `"brace-expansion": "^5.0.8"`,
which forces v5 on every consumer. That broke the toolchain: brace-expansion changed its entry
point between v2 and v5 —

```js
// v1 / v2                      // v5
module.exports = expand         module.exports = { expand, EXPANSION_MAX, ... }
```

— and `minimatch@3` / `minimatch@5` call the module's default export directly, so any pattern
containing a brace threw `TypeError: expand is not a function`. `minimatch@10` uses a named
import and was fine.

**Every gate stayed green while this was broken.** Lint, 70 tests, knip and the Storybook build
never fed a brace pattern to a glob, so the failure had no way to surface. Green CI is not the
same as a working toolchain.

The fix is the **scoped** form `"brace-expansion@5": "^5.0.8"` — take the patch wherever the
major already matches, and leave `minimatch@3`/`@5` on the line they can actually load. What
that leaves behind is allowlisted below, with the reasoning.

`tests/toolchain/brace-expansion-override.test.ts` now resolves every installed `minimatch` and
runs a brace pattern through each one, so a future override that reintroduces the break fails
CI instead of passing quietly.

## Current exceptions

| Advisory | Package | Why it does not apply here | Expires |
| --- | --- | --- | --- |
| [GHSA-mh99-v99m-4gvg](https://github.com/advisories/GHSA-mh99-v99m-4gvg) | `brace-expansion` (≤5.0.7) | `5.0.8` is taken wherever the major allows (`brace-expansion@5` override); `minimatch@3`/`@5` cannot load v5 (see above) and keep 1.1.16 / 2.1.3. Every remaining copy is **dev-only tooling**: `eslint-plugin-import`, `eslint-plugin-jsx-a11y` (via `@hideyukimori/nene2-standards`) and `@redocly/openapi-core` (via `openapi-typescript`) — all devDependencies. Measured 2026-07-29: `npm ls brace-expansion minimatch --omit=dev --all` → `(empty)`, and `npm run build` then grepping `dist/` for `brace-expansion`/`braceExpand` → **0 files**. Exploiting the OOM would need an attacker-supplied glob handed to our lint/codegen commands, which we run on our own source, never on untrusted input, never at runtime. | **2026-08-31** |
| [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2) | `react-router` (7.12.0–8.2.0) | The Deal console is a **static SPA built by Vite**. `src/app/router.tsx` is `createBrowserRouter` with **element-only routes** (13 routes, all `{ path, element }`) — **no RSC mode, no server components, no server-side route `action`/`loader`, no `@react-router/dev` runtime**. The advisory's attack path (a server executing a route action before returning 400) has no counterpart in a client-only bundle. Measured 2026-07-29 in this tree: route-level `action:`/`loader:` keys = 0 (the 3 textual matches are `DealActivity.action`, a domain field); `@react-router/dev` / `react-router/rsc` / `createStaticHandler` / `createStaticRouter` / `ServerRouter` / `StaticRouter` = 0; `node_modules/@react-router` absent. | **2026-08-31** |

There is **no fix available in the 7.x line**: `react-router-dom` ends at 7.18.1, and the fix
lands in `react-router` v8 (≥ 8.2.1) — a different package and a breaking upgrade. Note that
npm's own `fixAvailable` proposes `react-router-dom@7.11.0`; that is a **downgrade** back below
the four advisories 7.18.1 just fixed, so it is not a fix. The exception is removed by the
**react-router v8 migration wave** (bundled with the NENE2 RR8 re-evaluation).

## Fleet note

The fleet reference implementation is **contact** (#524, 施主 GO 2026-07-29); this repo follows
it. Each product must **verify the RSC-unused claim in its own tree before copying the allowlist
entry** — the measurements above were taken here, not inherited. Copying the exception without
re-measuring is exactly the failure mode the rules exist to prevent.

## Pins are time-limited, not fixes

Before this change the repo pinned `brace-expansion` per major (`@1=1.1.16`, `@2=2.1.2`,
`@5=5.0.7`) to dodge an earlier advisory. All three pinned versions fell **inside** the range of
the next one (`<=5.0.7`) — the pins became the vulnerability. Prefer ranges, and revisit pins
when an advisory touches the same package.

## Related

- [`coding-standards.md`](./coding-standards.md) — the wider merge-gate set
- [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) — the config and its reasoning

Last updated: 2026-07-29
