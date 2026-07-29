/**
 * Focus-ring capture — the route matrix in visual-smoke.mjs never tabs into
 * anything, so a focus-indicator change is invisible to it (that is exactly how
 * the 1.38:1 ring in #182 survived). This tabs to the login form's controls and
 * shoots the focused state, light and dark.
 *
 * Usage: npm run mock (another shell), then
 *   node tools/smoke/focus-shot.mjs before | after
 */
import { chromium } from 'playwright'
import fs from 'node:fs'

const LABEL = process.argv[2] || 'before'
const BASE = process.env.SMOKE_BASE || 'http://localhost:5187'
const OUT = process.env.SMOKE_OUT || `/tmp/deal-focus/${LABEL}`
fs.mkdirSync(OUT, { recursive: true })

const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } })
await page.goto(BASE, { waitUntil: 'networkidle' })
await page.waitForTimeout(600)

let shots = 0
for (const theme of ['light', 'dark']) {
  await page.evaluate((t) => {
    document.documentElement.setAttribute('data-theme', t)
  }, theme)
  await page.waitForTimeout(150)

  // email input focus
  const input = page.locator('input#login-email')
  if (await input.count()) {
    await input.focus()
    await page.waitForTimeout(120)
    await page.screenshot({ path: `${OUT}/input__${theme}.png`, clip: await boxOf(input) })
    shots++
  }
  // submit button focus (keyboard, so :focus-visible applies)
  const btn = page.locator('button[type="submit"]')
  if (await btn.count()) {
    await btn.evaluate((el) => {
      el.focus()
    })
    await page.keyboard.press('Shift+Tab')
    await page.keyboard.press('Tab')
    await page.waitForTimeout(120)
    await page.screenshot({ path: `${OUT}/button__${theme}.png`, clip: await boxOf(btn) })
    shots++
  }
}

async function boxOf(loc) {
  const b = await loc.boundingBox()
  if (!b) return { x: 0, y: 0, width: 400, height: 200 }
  const pad = 14
  return { x: b.x - pad, y: b.y - pad, width: b.width + pad * 2, height: b.height + pad * 2 }
}

console.log(`${LABEL}: ${String(shots)} focus shots -> ${OUT}`)
await browser.close()
