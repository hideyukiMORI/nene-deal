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

Re-measured 2026-08-08, now that the allowlist is empty and there is nothing left to remove:
flipping the config to `"low": true` makes the command exit `1` on the one remaining `low`;
flipping it back, exit `0`. An empty allowlist and a passing gate is a real result here, not a
gate that stopped looking.

> **Run it in `frontend/`.** `npm audit` in the repo root reports `0` vulnerabilities and exits
> `0` — there is no `package.json` there, so it succeeds at auditing nothing. The green is not a
> measurement. Every figure in this document was taken in `frontend/`.

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

Rule 4 does nearly all of the work. Of the **11** advisories present when the gate landed
(2026-07-29), **8 were removed by upgrading**:

| Change | Advisories removed |
| --- | --- |
| `brace-expansion@5` → `^5.0.8` (replacing three per-major pins) | `GHSA-mh99-v99m-4gvg` for every copy that can take v5 |
| `postcss` override → `^8.5.24` | `GHSA-r28c-9q8g-f849` (path traversal via `sourceMappingURL`) |
| `react-router-dom` `^7.9.6` → `^7.18.1` | `GHSA-chx6-hx7r-mcp5` (high, DoS via route matching) + `GHSA-wrjc-x8rr-h8h6`, `GHSA-h8fp-f39c-q6mh`, `GHSA-337j-9hxr-rhxg` (moderate) |

The 3 it could not remove became allowlist entries. On **2026-08-08** rule 4 took those too, and
the allowlist reached **zero** — see [How the four exceptions died](#how-the-four-exceptions-died-2026-08-08).
The only advisory left in the tree is a `low` (`esbuild`), covered under
[Known remaining finding](#known-remaining-finding).

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
that left behind was allowlisted until 2026-08-08, when a patch existed for every series.

`tests/toolchain/brace-expansion-override.test.ts` now resolves every installed `minimatch` and
runs a brace pattern through each one, so a future override that reintroduces the break fails
CI instead of passing quietly.

## Current exceptions

**None.** The allowlist has been empty since 2026-08-08.

## How the four exceptions died (2026-08-08)

Between 2026-07-29 and 2026-08-04 the allowlist grew to four entries, each arguing rule 4's
escape hatch — *no fix exists that we can adopt*. Re-checking them found something worse than
expiry: **all four reasons had become false**, and the entries were suppressing findings that
were by then fixable with a patch bump.

The check is two commands per entry — one for the advisory's liveness, one for its *reasoning*:

```bash
npm audit --json | grep <GHSA>                      # is it still here?
gh api /advisories/<GHSA> \
  --jq '.vulnerabilities[]|"\(.package.name) \(.vulnerable_version_range) → \(.first_patched_version)"'
```

| Advisory | Package | What the entry claimed | What was actually true on 2026-08-08 |
| --- | --- | --- | --- |
| [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2) | `react-router` | fix only in v8 (≥8.2.1), a breaking upgrade | **7.18.2** — backported into the 7.x line; range re-scoped to `<7.18.2` |
| [GHSA-mh99-v99m-4gvg](https://github.com/advisories/GHSA-mh99-v99m-4gvg) | `brace-expansion` | the fix exists only as 5.0.8 | **1.1.17 / 2.1.3 / 5.0.8** — patched in every series |
| [GHSA-rgw5-rvv9-x895](https://github.com/advisories/GHSA-rgw5-rvv9-x895) | `brace-expansion` | v5 only; the 1.x/2.x copies are unavoidable | **1.1.18 / 2.1.4 / 5.0.9** — patched in every series |
| [GHSA-7p8r-x3mc-p8w7](https://github.com/advisories/GHSA-7p8r-x3mc-p8w7) | `fast-uri` | only 4.1.2, outside ajv's `^3.0.1` | **3.1.5** — inside ajv's declared range |

### The lesson: an exception has two ways to rot, and the expiry date catches only one

1. **The advisory stops applying.** Caught automatically — `npm audit` stops reporting it.
2. **The stated reason stops being true.** Caught by *nothing*. Advisories get re-scoped and
   patches get backported after publication, so a carefully measured "there is no fix" quietly
   becomes a false statement while the entry keeps suppressing a now-fixable finding — and every
   gate stays green throughout.

None of these four had reached its 2026-08-31 expiry. Waiting for the date would have carried
four suppressed, fixable high advisories for another three weeks. **Re-verify the reasoning at
every touch, not at expiry.**

### What the fix cost

Nothing structural: `npm update` on the six affected packages. No `package.json` change, no
`overrides` change, **no new allowlist entry**, 32 lines of lockfile.

| Package | Before → after |
| --- | --- |
| `brace-expansion` | 1.1.16 → **1.1.18**, 2.1.3 → **2.1.4**, 5.0.8 → **5.0.9** |
| `fast-uri` | 3.1.4 → **3.1.5** |
| `js-yaml` | 4.3.0 → **4.3.1** |
| `nanoid` | 3.3.16 → **3.3.18** |
| `react-router` / `react-router-dom` | 7.18.1 → **7.18.2** |

High advisories: **7 → 0**.

### Two things underneath the removed entries are still load-bearing

**The scoped `"brace-expansion@5": "^5.0.8"` override stays.** It is not residue from the
removed entries — it is what keeps `minimatch@3`/`@5` off the v5 entry point (see
[above](#an-override-is-a-code-change-not-a-version-number)), and its caret is what let 5.0.9
in. Positive control, measured 2026-08-08 in this tree:

```
node -e "const b=require('brace-expansion'); b('a{b,c}')"
  → TypeError: b is not a function     (typeof object; keys: EXPANSION_MAX, EXPANSION_MAX_LENGTH, expand)
minimatch@3.1.5 → brace-expansion@1.1.18   ·   minimatch@5.1.9 → 2.1.4   ·   minimatch@10.2.5 → 5.0.9
```

Note where that failure surfaces: **nowhere on its own.** The 2026-07-29 round established that
lint, the test suite, knip and the Storybook build all stayed green while the override was
broken, because none of them happened to feed a brace pattern to a glob — so "the gates passed"
is not evidence the override is safe. `tests/toolchain/brace-expansion-override.test.ts` is the
only thing that answers the question: it resolves every installed minimatch and runs a brace
pattern through each. Run it — not just CI — before touching the override.

**The flat `"js-yaml": "^4.3.0"` override stays.** `@redocly/openapi-core` pins js-yaml
*exactly* (`"js-yaml": "4.2.0"` in its `package.json`), so GHSA-5p4m-2wfm-xmqj could not be
answered by a lockfile refresh alone. It moved to 4.3.1 only because that flat override already
existed and accepts it. Remove the override as "unnecessary" and js-yaml silently falls back to
the pinned 4.2.0, bringing the advisory back.

## Known remaining finding

`esbuild` [GHSA-g7r4-m6w7-qqqr](https://github.com/advisories/GHSA-g7r4-m6w7-qqqr) (**low**),
installed 0.27.7, vulnerable 0.27.3–0.28.0, patched 0.28.1. **Deliberately not fixed** — a
recorded decision, not an oversight:

- It is **below this gate** (`"moderate": false`), so CI is green with it present.
- It is not reachable here: the advisory is an arbitrary file read by the esbuild **dev server
  on Windows**, and esbuild is absent from the production tree (`npm ls esbuild --omit=dev
  --all` → `(empty)`, measured 2026-08-08).
- It cannot be taken cheaply: `npm update esbuild vite` leaves esbuild at 0.27.7 (vite's
  transitive pin holds it) while churning 367 lines of lockfile. The fix rides along with a
  **vite major upgrade**, which hub carries as a fleet-wide wave on the board — several repos
  hold the same low. Do not open a one-repo bump for it.

## Production exposure

Measured 2026-08-08, `npm ls <pkg> --omit=dev --all` for each package that has ever appeared in
this file — `brace-expansion`, `js-yaml`, `nanoid`, `fast-uri`, `esbuild` — all returned
`(empty)`. Every finding in this tree, past and present, has been **dev-only tooling**. Moderate
count is 0, so nothing slipped in under the gate either.

## Fleet note

The fleet reference implementation is **contact** (#524, 施主 GO 2026-07-29); this repo follows
it, and is the **fourth** to reach an empty allowlist. Each product must **re-measure in its own
tree** rather than copy either an exception or a removal — the figures above were taken here.
Copying without re-measuring is exactly the failure mode the rules exist to prevent, and the
2026-08-08 round is the proof: the entries being copied around the fleet were, by then, arguing
things that were no longer true.

## Pins are time-limited, not fixes

Before this change the repo pinned `brace-expansion` per major (`@1=1.1.16`, `@2=2.1.2`,
`@5=5.0.7`) to dodge an earlier advisory. All three pinned versions fell **inside** the range of
the next one (`<=5.0.7`) — the pins became the vulnerability. Prefer ranges, and revisit pins
when an advisory touches the same package.

## Related

- [`coding-standards.md`](./coding-standards.md) — the wider merge-gate set
- [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) — the config and its reasoning

Last updated: 2026-08-08
