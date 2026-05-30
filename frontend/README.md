# NeNe Deal — Frontend

React + TypeScript SPA for the NeNe Deal pipeline (kanban board, forecast, won → Invoice handoff).

Policy: this app follows the NeNe Records frontend standards
([`nene-records/docs/development/frontend-standards.md`](https://github.com/hideyukiMORI/nene-records))
— layered `app → pages → features → entities → shared`, zero-tolerance placement, theme-token-only
styling, Storybook contracts, MSW tests.

**Languages:** Japanese (`ja`, default) and English (`en`) only — see ADR 0004. `ja` is the
source-of-truth message catalog.

## Commands

```bash
npm install
npm run dev          # http://localhost:5173 (API proxied to the PHP app)
npm run mock         # dev server backed by MSW handlers (no PHP needed)
npm run storybook    # component catalog on :6006
npm run codegen      # regenerate src/shared/api/schema.gen.ts from ../docs/openapi/openapi.yaml
npm run check        # type-check, lint, format, test, knip, build-storybook
```

## Structure

- `src/app/` — providers, router, root error boundary
- `src/pages/` — route wiring
- `src/features/` — user workflows (hooks + presentational UI)
- `src/entities/` — API resource slices (DTO ↔ model, query keys, TanStack hooks)
- `src/shared/` — `api` (transport), `i18n` (ja/en), `ui` (theme tokens + primitives), `config`, `lib`

Theme swap: edit `src/shared/ui/theme/active.css` import only.
