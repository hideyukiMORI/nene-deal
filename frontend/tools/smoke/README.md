# Visual smoke harness

Headless before/after screenshot comparison for CSS refactors that must be
**appearance-preserving** (W-Layer `@layer` wrapping, W-Spec `:where` flattening,
Wave G gate validation). Used to prove 0-pixel diff across design × theme × routes.

Reference implementation for the fleet's CSS re-generation lane.

## Usage

```sh
npm run mock                 # terminal 1: mock server (VITE_MOCK_API, :5187)
npm run smoke -- before      # capture the baseline
# ...apply the CSS change...
npm run smoke -- after       # capture after the change
npm run smoke:diff           # pixel-diff; exits non-zero if any pixel differs
```

Matrix: `{login, board, deal-detail, stages, users, audit, settings} × {calm} ×
{light, dark} × {1280px, 390px}` = **28 shots**.

The four admin screens joined in #192 (they were unreachable until the mock grew
a `/auth/me` handler). The 390px column joined in the D wave3 prep: the shell
swaps at `max-width: 1024px`, so a 1280px-only matrix rendered **none** of
`.m-topbar` / `.m-tabs` / `.m-sheet-wrap` — draining those would have diffed 0px
while breaking every phone.

Both additions are guarded rather than trusted:

- **Route coverage** — reaching fewer than all four admin routes exits 1.
- **Breakpoint coverage** — `.m-tabs` must actually flip (`display: none` @1280 →
  `grid` @390) before any shot is kept. A second viewport that never crosses the
  breakpoint is just a narrow desktop, and 0px over it would prove nothing.
- **Matrix drift** — `smoke:diff` walks the _before_ set, so shots that exist only
  in _after_ used to be skipped in silence. It now reports `MATRIX GREW` and
  exits non-zero, so a before-set captured with an older matrix can never read as
  a clean run.

> `enterprise` was dropped from the matrix in C5 W3 (#169): the app hard-codes
> `data-design='calm'` (index.html + `shared/theme`) and ships no switcher, so
> the `[data-design='enterprise'|'console']` rules were unreachable dead CSS and
> were removed. Forcing the attribute here used to render a design that cannot
> occur in the product.

Design/theme are forced via `html[data-design]`/`[data-theme]`. Auth token is
in-memory, so we log in once and navigate client-side (no reload).

Env overrides: `SMOKE_BASE`, `SMOKE_OUT`, `SMOKE_BEFORE`, `SMOKE_AFTER`, `SMOKE_DIFF`.

## Coverage note

Still **not** captured, so do not read a 0px run as covering them:

- `.board.is-dragging` / `.deal-drag` / `.is-ghost` / `.drop-marker` — drag
  transients, only alive during a pointer gesture.
- The mobile sheet in its **open** state (`.m-sheet-wrap.open`). The 390px column
  renders the closed shell; opening the sheet needs a tap step that the harness
  does not perform yet.

For those, verify by mechanical argument (specificity-preserving transforms) plus
`stylelint` — and note that four of them are reached from JS rather than JSX
(`classList` / `className =` in `use-kanban-dnd.ts`), so a `className` grep will
report them as unreferenced.
