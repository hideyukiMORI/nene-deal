import { defineConfig, devices } from '@playwright/test'

// Live-target harness — owner-review material for a kit-migration wave (#225).
//
// 🔴 This is the SEPARATE live lane. `playwright.config.ts` is hermetic and carries a guard
// that hard-fails on any non-local host; this one deliberately drives the REAL public demo
// so the left column of the bundle is what the owner's browser actually gets. The two
// configs must stay apart: relaxing the guard in the hermetic config would let a CI run
// reach production, and the ruling (fleet 2026-07-21, quoted in nene-vault's spec) is that
// CI never touches production. **Do not wire this into CI.**
//
// Safety: this lane only ever *reads*. `/demo/standard` mints a throwaway `demo-…` org with
// a 3h TTL that the nightly cron reseeds anyway (docs/demo.md §0), so nothing it touches
// outlives the run and no real organisation is involved.
//
// Usage:
//   # local target up first (docker compose app + `npm run dev`), then
//   npm run e2e:live --prefix frontend
//   # point at a built preview instead of the dev server
//   NENE_DEAL_OWNER_REVIEW_LOCAL_URL=http://localhost:4173 npm run e2e:live --prefix frontend
//   # fixed output directory (default: today's date)
//   NENE_DEAL_OWNER_REVIEW_DIR=w1 npm run e2e:live --prefix frontend

const BASE_URL = process.env.NENE_DEAL_LIVE_BASE_URL ?? 'https://deal.ayane.co.jp'

export default defineConfig({
  testDir: '../tests/e2e/live',
  fullyParallel: false, // one demo org at a time; keep the run sequential and gentle
  forbidOnly: Boolean(process.env.CI),
  retries: 0,
  workers: 1,
  reporter: [['list']],
  timeout: 60_000,
  use: {
    baseURL: BASE_URL,
    // Real network to the live demo; no webServer here on purpose.
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
