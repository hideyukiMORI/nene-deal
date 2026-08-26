# Owner-review material — how deal makes it (#225)

**What this is.** The material the owner looks at before a kit-migration wave goes to
production: the same screens on production and on a local build, side by side in one HTML
file. **Once per wave, not per PR.** The acceptance criterion, in the owner's words:
*「アプリケーションが正常に動くこと、人間が見てインターフェースが整っていること」* — it
works, and it looks put together. Not "it is identical", not "it conforms".

**What it is not.** Not a comparison and not a gate that decides anything. It only shows
pictures. A person decides.

Written from `nene-vault/docs/qa/owner-review/README.md` and its
`batch8-owner-review.spec.ts`, per that README's §"For another ship".

## Run

```bash
# 1. local target up: docker compose app with DEMO_MODE=1, and the frontend dev server
DEMO_MODE=1 docker compose up -d        # DEMO_MODE は compose の pass-through
npm run dev --prefix frontend           # 5187（strictPort）

# 2. capture
npm run e2e:live --prefix frontend
#   NENE_DEAL_OWNER_REVIEW_LOCAL_URL=http://localhost:4173   # a built preview instead
#   NENE_DEAL_OWNER_REVIEW_DIR=w1                            # fixed directory (default: today)
```

Output: `docs/qa/owner-review/<name>/index.html` + PNGs + `meta.json`. ~45 s, 28 cells
(7 screens × 2 viewports × 2 sides; 2 are "not applicable"). **Gitignored** — the material is
per-wave and disposable. What persists is the verdict on the issue and this generator.

Both sides are seated through `/demo/standard` (throwaway `demo-…` org, 3h TTL, no
credentials). One org per side per run.

## Read

| column | is |
| --- | --- |
| **production** | whatever `deal.ayane.co.jp` served at run time — **not "the current design"**. Establish which build that is before reading a row. For W1 it is `e361d3e` (2026-08-23 release), confirmed pre-kit: `git grep nene2-ui e361d3e -- frontend/` returns nothing |
| **local** | the build named in `meta.json` (`local HEAD`, `local worktree`, `local nene2-ui`) |

Content differs between the columns by design — each side mints its own disposable org — so
**look at the chrome**: buttons, inputs, badges, the sheet, the nav. Not at the rows.

Screens: `board` · `deal-detail` · `stages` · `users` · `audit` · `settings` ·
`user-sheet` (mobile only, viewport-only), each at desktop 1280×800 and mobile 375×812.

A cell reading **not captured** carries the reason. It is reported, never dropped: a screen
that could not be reached is a finding, not a blank.

## Record

The verdict is **GO / NG per screen, on the wave's tracking issue** (for W1: #225), with the
`meta.json` values quoted so the verdict is tied to what was looked at. Expect an NG round:
vault's first W1b bundle came back GO 4 / NG 3 because the kit's default look replaced the
product's, and the fix was slot values and `className`, not a revert — so ship those *with*
the migration (deal's are in `frontend/src/shared/ui/theme/themes/kit-slots.css`).

## Guards — and why each one exists

Every one of these was added because the harness produced something that looked fine.

- **Seated.** Checked after *every* navigation, not once at the start.
  🔴 The first version navigated with `page.goto` and **photographed the login page for all
  ten screens, then reported success** — every file written, no error raised. The auth store
  is memory-only on purpose (`shared/auth/demo-seat.ts`: "reloading ends the session"), so a
  full page load logs the visitor out. The give-away was byte sizes: the mobile shots were
  all *exactly* 22,002 bytes. **A screenshot harness cannot tell a screen from a login form.**
  Fixed by reading the bearer off the wire once and re-parking it with `addInitScript` before
  every load — and by asserting the seat every time, because the re-park could fail too.
- **Unstyled build.** Checked on `/` (the board) on the local side before anything is
  captured — the `@source` regression that `npm run source-probe` also covers. Not on a
  landing page: deal's own markup carries no kit utility even when fully styled, so a guard
  there passes on a build that generated none of the kit's classes.
- **Unique selector.** The user sheet is `.m-sheet-wrap.open .m-sheet[role="dialog"]`, and the
  harness asserts that matches exactly one element. `.m-sheet[role="dialog"]` alone matches
  two — the shell carries its own account sheet with the same class and role — and a probe
  that quietly picks one of two still produces a picture.
- **Nothing captured on either side** fails the run. Partial capture is reported in the table.
- **`local worktree`** is in `meta.json` and says `clean` or `🔴 DIRTY`. vault #443: a bundle
  that names only its HEAD can be a build with uncommitted changes in it.

## What the owner is being asked to accept (W1)

Two lists, kept apart because they are two different decisions. **Neither is a defect
report** — they are the places where "it looks put together" was chosen over "it is
byte-identical to production", and the reason for each.

### A. Snapped to the kit's scale — small, visual

deal's production value is **not a step on the kit's scale**, and the kit's `slot-values`
checker rejects a literal (inventing a step is forbidden). So the kit's default ships.

**Every row here was measured**, both sides, `getComputedStyle` on the same element of
`/settings`, on nene2-ui 0.19.0 against production `e361d3e` (2026-08-26). Nothing in this
table was reasoned from the CSS source — see the warning below for why that matters.

| where | production | ships as | delta |
| --- | --- | --- | --- |
| Badge `gap` | 5px | 4px | −1px |
| Badge `font-size` | 11.5px | 11px | −0.5px |
| Badge `padding-block` | 2px | 4px | +2px |
| Button `padding-block` | 10px | 12px | +2px |
| Button `font-size` | 13.5px | 14px | +0.5px |
| Input / Select `padding-block` | 11px | 12px | +1px |
| Input / Select `padding-inline` | 14px | 12px | −2px |
| Input / Select `border-radius` | 13px | 12px | −1px |
| Field label `font-size` | 12.5px | 12px | −0.5px |

The knock-on: the primary button is **4.8px taller** and a control **2px taller** than in
production. Nothing else moves.

The spacing scale is 4 / 8 / 12 / 16 / 20 / 24 / 32 / 48px — 5px, 10px, 11px, 13px, 13.5px
and 14px are simply not on it. nene-vault's badge gap is 6px, so matching either ship's
number would canonise one ship's drift as the fleet's step.

**Two values are not steps and are written anyway** — they are not sizes, so the scale has
nothing to say about them: the danger button's background is the keyword `transparent` (the
kit has no transparent token), and its border is
`color-mix(in oklch, var(--danger) 40%, var(--line))`, copied from what production's
`.btn-danger` measures. The contract has no key for that mix, so writing the vocabulary
would change the colour rather than move it (判例24).

Everything else that differed **was written**, in
`frontend/src/shared/ui/theme/themes/kit-slots.css`, by pointing a slot at a step that
already exists — never by inventing one: badge and button radius (both are pills; the kit's
8px box is a different shape, not a near miss), button `padding-inline` 20px, button `gap`
8px, button and label weight 600, control font 14px, the field label's colour, and the
accent, border, danger-outline and soft-pill colours. All exact matches.

⚠️ The three Badge rows **only hold on nene2-ui 0.19.0** (fleet-tooling#463 / PR #470).
Before 0.19.0 the kit's Badge has no gap and inherits body type — 14px / 400 — which is a
visibly different part, not a snap. **Capture the bundle on 0.19.0 or the badges in it are
not the ones being proposed**; `meta.json` names the installed version, so check that field
before reading a badge.

🔴 **Measure the rendered value; do not read deal's CSS for it.** The button rows above were
missing from the first version of this table — the migration had matched the button's
colours and left its shape alone, and reading `styles.css` would not have caught it either:
the base `.btn` says `border-radius: 4px; padding: 9px 15px`, and **none of that renders**,
because `[data-design='calm'] .btn` (always on) overrides it with a pill and 10/20px. The
accent colour has the same shape — `--accent` is re-declared as a literal in the calm skin.
**deal's stylesheet has a layer that describes a design nobody sees.**

### B. Behaviour that changed on purpose — not visual, and not reversible by a slot

- **Toasts last 5s, not deal's 2.6s.** A notification that disappears before a screen
  reader finishes reading it was never delivered.
- **The toast live region now exists before there is anything to announce.** deal's own
  `Toaster` returned `null` on an empty queue, so the region was born together with its
  first toast — on screen, and silent to assistive technology.
- **`EmptyState` is `role="status"`, no longer `<h2>`.** The wording is unchanged; the
  empty-state text has left heading navigation.
- **`useToast` outside its provider throws** instead of doing nothing.

## For another ship

Copy `tests/e2e/live/owner-review.spec.ts` and `frontend/playwright.live.config.ts`, replace
`SCREENS`, keep the two viewports and the two-column `index.html`.

🔴 **Do not wire it into CI.** CI never touches production (fleet ruling 2026-07-21). deal's
hermetic `playwright.config.ts` carries a guard that hard-fails on non-local hosts; that guard
is the reason this lane needs a config of its own rather than a relaxed flag.

🔴 **Find out how your demo seat survives a reload before you write the navigation.** deal's
does not, and the failure is silent.
