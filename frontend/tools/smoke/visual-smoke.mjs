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
for (const d of ['calm', 'enterprise']) for (const t of ['light', 'dark']) VARIANTS.push({ d, t })

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
    await page.waitForTimeout(120)
    await page.screenshot({ path: `${OUT}/${name}__${d}-${t}.png`, fullPage: true })
  }
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
await shot(page, '2-board')

if (await clickBtn(page, /Details|詳細/)) {
  await shot(page, '3-deal')
  await clickBtn(page, /Pipeline|パイプライン/)
} else console.log('note: no Details link reached (deal-detail skipped)')

await browser.close()
console.log(`${LABEL}: ${fs.readdirSync(OUT).length} shots -> ${OUT}`)
