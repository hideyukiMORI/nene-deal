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
npm run dev          # http://localhost:5187 (API proxied to the PHP app)
npm run mock         # dev server backed by MSW handlers (no PHP needed)
npm run storybook    # component catalog on :6106
npm run codegen      # regenerate src/shared/api/schema.gen.ts from ../docs/openapi/openapi.yaml
npm run check        # type-check, lint, format, test, knip, build-storybook
```

The client calls the backend under `/api/v1` (matching the OpenAPI contract). Optional
`.env` for talking to a real, secured backend:

```dotenv
VITE_API_BASE_URL=        # empty = same-origin via the dev proxy
VITE_ORG_SLUG=            # sent as X-Organization-Slug (omit for single-tenant)
VITE_API_KEY=             # sent as X-NENE2-API-Key on writes (omit when the API is open)
VITE_REQUIRE_LOGIN=       # 'true' gates the app behind /login (set when the backend enforces JWT)
```

Operator login: `/login`. The bearer token is held in memory only (lost on reload — log
in again); a future ADR may move to an httpOnly cookie.

## Structure

- `src/app/` — providers, router, root error boundary
- `src/pages/` — route wiring
- `src/features/` — user workflows (hooks + presentational UI)
- `src/entities/` — API resource slices (DTO ↔ model, query keys, TanStack hooks)
- `src/shared/` — `api` (transport), `i18n` (ja/en), `ui` (theme tokens + primitives), `config`, `lib`

Theme swap: edit `src/shared/ui/theme/active.css` import only.
