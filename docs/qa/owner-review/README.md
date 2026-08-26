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

## For another ship

Copy `tests/e2e/live/owner-review.spec.ts` and `frontend/playwright.live.config.ts`, replace
`SCREENS`, keep the two viewports and the two-column `index.html`.

🔴 **Do not wire it into CI.** CI never touches production (fleet ruling 2026-07-21). deal's
hermetic `playwright.config.ts` carries a guard that hard-fails on non-local hosts; that guard
is the reason this lane needs a config of its own rather than a relaxed flag.

🔴 **Find out how your demo seat survives a reload before you write the navigation.** deal's
does not, and the failure is silent.
