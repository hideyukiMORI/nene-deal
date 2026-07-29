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

Matrix: `{login, board, deal-detail} × {calm} × {light, dark}`.

> `enterprise` was dropped from the matrix in C5 W3 (#169): the app hard-codes
> `data-design='calm'` (index.html + `shared/theme`) and ships no switcher, so
> the `[data-design='enterprise'|'console']` rules were unreachable dead CSS and
> were removed. Forcing the attribute here used to render a design that cannot
> occur in the product.

Design/theme are forced via `html[data-design]`/`[data-theme]`. Auth token is
in-memory, so we log in once and navigate client-side (no reload).

Env overrides: `SMOKE_BASE`, `SMOKE_OUT`, `SMOKE_BEFORE`, `SMOKE_AFTER`, `SMOKE_DIFF`.

## Coverage note

Mobile sheet, `.board.is-dragging` (drag-transient) and the Account-menu
routes (users/stages/settings) are not captured statically; verify those by
mechanical argument (specificity-preserving transforms) plus `stylelint`.
