/**
 * Visual smoke harness — captures a fullPage screenshot matrix of the running
 * mock app across design × theme × key routes, for before/after CSS refactors
 * (W-Layer / W-Spec / Wave G gate validation). Reference impl for the fleet.
 *
 * Usage:
 *   npm run mock            # start the mock server (VITE_MOCK_API, port 5187) in another shell
 *   npm run smoke -- before # capture the "before" set
 *   ...apply CSS change...
 *   npm run smoke -- after  # capture the "after" set
 *   npm run smoke:diff      # pixel-diff before vs after (0 = appearance unchanged)
 *
 * Design/theme are forced via html[data-design]/[data-theme] attributes (the app
 * sets data-design='calm' on init; we override post-load). Token auth is in-memory
 * (not persisted) so we log in once and navigate client-side (no reload).
 */
import { chromium } from 'playwright'
import fs from 'node:fs'

const LABEL = process.argv[2] || 'before'
const BASE = process.env.SMOKE_BASE || 'http://localhost:5187'
const OUT = process.env.SMOKE_OUT || `/tmp/deal-smoke/${LABEL}`
const CREDS = { email: 'operator@nene-deal.test', password: 'password' }
const VARIANTS = []
// 'calm' is the only design the app ships: index.html and shared/theme both
// hard-code data-design='calm' and there is no switcher. The 'enterprise' and
// 'console' skins were unreachable dead CSS and were removed in C5 W3 (#169) —
// capturing them here would screenshot a design that cannot occur in the app.
for (const d of ['calm']) for (const t of ['light', 'dark']) VARIANTS.push({ d, t })

// The shell swaps at 1024px: the desktop `.topnav` is hidden and `.m-topbar` /
// `.m-tabs` / `.m-sheet-wrap` take over (designs.css @media (max-width:1024px)).
// A single 1280px matrix therefore renders NONE of the mobile shell — draining
// those classes would diff 0px while breaking every phone, which is the same
// shape as the admin routes that were invisible until #192. Both widths are
// captured, and `assertBreakpointLive` proves the small one really crosses the
// breakpoint rather than just being a narrower desktop.
const VIEWPORTS = [
  { vp: 'w1280', width: 1280, height: 900 },
  { vp: 'w390', width: 390, height: 844 },
]

fs.mkdirSync(OUT, { recursive: true })

const shot = async (page, name) => {
  for (const { d, t } of VARIANTS) {
    await page.evaluate(
      ([d, t]) => {
        const e = document.documentElement
        e.setAttribute('data-design', d)
        e.setAttribute('data-theme', t)
      },
      [d, t],
    )
    for (const { vp, width, height } of VIEWPORTS) {
      await page.setViewportSize({ width, height })
      await page.waitForTimeout(120)
      await page.screenshot({ path: `${OUT}/${name}__${d}-${t}__${vp}.png`, fullPage: true })
    }
    await page.setViewportSize({ width: VIEWPORTS[0].width, height: VIEWPORTS[0].height })
  }
}

/**
 * Positive control for the matrix itself: capturing a second width proves
 * nothing unless that width actually selects the mobile shell. Measure the one
 * element the breakpoint owns (`.m-tabs`, `display:none` → `grid`) at both
 * widths and fail loudly if it does not flip — otherwise a later 0px result
 * would be "the mobile CSS never rendered", not "the mobile CSS is unchanged".
 */
const assertBreakpointLive = async (page) => {
  const read = async (width) => {
    await page.setViewportSize({ width, height: 844 })
    await page.waitForTimeout(200)
    return page.evaluate(() => {
      const el = document.querySelector('.m-tabs')
      return el ? getComputedStyle(el).display : '(no .m-tabs in DOM)'
    })
  }
  const wide = await read(1280)
  const narrow = await read(390)
  await page.setViewportSize({ width: 1280, height: 900 })
  if (wide === narrow || narrow === '(no .m-tabs in DOM)') {
    console.error(
      `ERROR: the 1024px breakpoint did not engage (.m-tabs display: ${wide} @1280 vs ${narrow} @390). ` +
        `The mobile shots would be narrow desktops, so a 0px diff over them would prove nothing.`,
    )
    await page.context().browser()?.close()
    process.exit(1)
  }
  console.log(`breakpoint control: .m-tabs display ${wide} @1280 → ${narrow} @390 ✓`)
}
const clickBtn = async (page, re) => {
  const l = page.getByRole('button', { name: re }).first()
  if (await l.count()) {
    await l.click()
    await page.waitForTimeout(500)
    return true
  }
  return false
}

const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } })
page.on('pageerror', (e) => console.log('PAGEERR', e.message))

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
await shot(page, '1-login')

await page.fill('input[type="email"], input[name="email"]', CREDS.email)
await page.fill('input[type="password"], input[name="password"]', CREDS.password)
await page.click('button[type="submit"]')
await page
  .waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 8000 })
  .catch(() => console.log('login nav timeout'))
await page.waitForTimeout(700)
await assertBreakpointLive(page)
await shot(page, '2-board')

if (await clickBtn(page, /Details|詳細/)) {
  await shot(page, '3-deal')
  await clickBtn(page, /Pipeline|パイプライン/)
} else console.log('note: no Details link reached (deal-detail skipped)')

// Admin routes. These were invisible to the smoke until the mock grew a
// `/auth/me` handler (#191): without it `useCurrentUser` fails, AppShell falls
// back to non-admin, and the admin nav never renders — so Stages/Users/Audit/
// Settings had never been captured. They are also where a nav/shell regression
// would show up first, which is exactly the D family's blast radius.
const ADMIN_ROUTES = [
  ['4-stages', /^Stages$|ステージ/],
  ['5-users', /^Users$|ユーザー/],
  ['6-audit', /^Audit$|監査/],
  ['7-settings', /^Settings$|設定/],
]
let reached = 0
for (const [name, re] of ADMIN_ROUTES) {
  if (await clickBtn(page, re)) {
    await shot(page, name)
    reached += 1
  } else console.log(`note: admin route ${name} not reached`)
}
// Fail loudly rather than silently shrinking the matrix: a smoke that quietly
// captures fewer screens still exits 0 and every later diff looks clean.
if (reached !== ADMIN_ROUTES.length) {
  console.error(
    `ERROR: reached ${reached}/${ADMIN_ROUTES.length} admin routes — the matrix is incomplete, so a 0px diff would be meaningless.`,
  )
  await browser.close()
  process.exit(1)
}

await browser.close()
console.log(`${LABEL}: ${fs.readdirSync(OUT).length} shots -> ${OUT}`)
