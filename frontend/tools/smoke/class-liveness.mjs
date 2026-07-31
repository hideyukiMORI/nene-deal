/**
 * legacy クラスが **各 call site で実際に効いているか** を全数判定する。
 *
 * drain の前提「このクラスがこの宣言を供給している」は、call site ごとに真偽が
 * 違う。同じ `.g6` でも、`.shell-calm .content { gap: var(--s8) }` のような
 * 子孫セレクタ（詳細度 0,2,0）に負けている要素では **既に死んでいる**。
 * そこを utility へ移すと、utility は `@layer legacy` に無条件で勝つため、
 * 死んでいた値が突然生き返って**見た目が変わる**。
 *
 * 2026-07-31 spacing-b で実際に起きた: `.g6` を `gap-6` にしたところ
 * `.content` の gap が 32px→24px（@1280）・20px→24px（@390）に変わった。
 * `.g6` は元から効いておらず、勝っていたのは `.shell-calm .content` と
 * `@media (max-width:1024px)` 版だった。値が 1:1 対応していても、
 * **供給元が違えば結果は変わる**。
 *
 * 判定方法: 対象クラスを DOM から外し、computed 値が変わるかを見る。
 *   変わる  → そのクラスが供給元 = 移行してよい
 *   変わらない → 別のルールが勝っている = **park**（レイヤ順で分割不能）
 *
 * 使い方:
 *   npm run mock
 *   PROBE_CLASSES=g1,g2,g3,g4,g5,g6 PROBE_PROP=gap \
 *     node tools/smoke/class-liveness.mjs out.json
 */
import { chromium } from 'playwright'
import fs from 'node:fs'

const BASE = process.env.PROBE_BASE || 'http://localhost:5187'
const PROP = process.env.PROBE_PROP || 'gap'
const CLASSES = (process.env.PROBE_CLASSES || 'g1,g2,g3,g4,g5,g6').split(',')
const OUT = process.argv[2] || '/tmp/class-liveness.json'
const CREDS = { email: 'operator@nene-deal.test', password: 'password' }

const SCAN = ([classes, prop]) => {
  const pathOf = (el) => {
    const seg = []
    for (let e = el; e && e !== document.body; e = e.parentElement) {
      const p = e.parentElement
      if (!p) break
      seg.unshift(`${e.tagName.toLowerCase()}:${Array.prototype.indexOf.call(p.children, e) + 1}`)
    }
    return seg.join('/')
  }
  const rows = []
  for (const cls of classes) {
    for (const el of document.querySelectorAll(`.${cls}`)) {
      const before = getComputedStyle(el).getPropertyValue(prop)
      el.classList.remove(cls)
      const after = getComputedStyle(el).getPropertyValue(prop)
      el.classList.add(cls)
      rows.push({
        cls,
        className: el.className,
        path: pathOf(el),
        before,
        after,
        live: before !== after,
      })
    }
  }
  return rows
}

const clickBtn = async (page, re) => {
  const l = page.getByRole('button', { name: re }).first()
  if (await l.count()) {
    await l.click()
    await page.waitForTimeout(450)
    return true
  }
  return false
}

const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } })
page.on('pageerror', (e) => console.log('PAGEERR', e.message))
const rows = []

const scan = async (route) => {
  for (const [vp, width, height] of [
    ['w1280', 1280, 900],
    ['w390', 390, 844],
  ]) {
    await page.setViewportSize({ width, height })
    await page.waitForTimeout(150)
    for (const r of await page.evaluate(SCAN, [CLASSES, PROP])) rows.push({ route, vp, ...r })
  }
  await page.setViewportSize({ width: 1280, height: 900 })
  await page.waitForTimeout(100)
}

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
await scan('1-login')
await page.fill('input[type="email"], input[name="email"]', CREDS.email)
await page.fill('input[type="password"], input[name="password"]', CREDS.password)
await page.click('button[type="submit"]')
await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 8000 }).catch(() => {})
await page.waitForTimeout(700)
await scan('2-board')
if (await clickBtn(page, /Add deal|ディールを追加/)) await scan('2b-board-createdeal')
if (await clickBtn(page, /Details|詳細/)) {
  await scan('3-deal-detail')
  await clickBtn(page, /Pipeline|パイプライン/)
}
const ADMIN = [
  ['4-stages', /^Stages$|ステージ/, /Add stage|ステージを追加/],
  ['5-users', /^Users$|ユーザー/, /Add user|ユーザーを追加/],
  ['6-audit', /^Audit$|監査/, null],
  ['7-settings', /^Settings$|設定/, null],
]
let reached = 0
for (const [name, navRe, formRe] of ADMIN) {
  if (await clickBtn(page, navRe)) {
    reached += 1
    await scan(name)
    if (formRe && (await clickBtn(page, formRe))) await scan(`${name}-form`)
  }
}
if (reached !== ADMIN.length) {
  console.error(
    `ERROR: admin ${reached}/${ADMIN.length} — 網が不完全。この結果で park 判定をしてはいけない。`,
  )
  await browser.close()
  process.exit(1)
}

fs.writeFileSync(OUT, JSON.stringify({ prop: PROP, classes: CLASSES, rows }, null, 2))

// className ＋ 勝っている値でまとめる（同じ call site が複数ルートに出るため）
const bySite = new Map()
for (const r of rows) {
  const k = `${r.cls} | ${r.className} | ${r.vp}`
  if (!bySite.has(k)) bySite.set(k, { ...r, routes: new Set() })
  bySite.get(k).routes.add(r.route)
  if (!r.live) bySite.get(k).live = false
}
const dead = [...bySite.values()].filter((r) => !r.live)
console.log(`要素 ${rows.length} 件 / call site×vp ${bySite.size} 件`)
console.log(`live（そのクラスが供給元・移行可）: ${bySite.size - dead.length}`)
console.log(`DEAD（別ルールが勝っている・**park**）: ${dead.length}`)
for (const d of dead) {
  console.log(`  [${d.cls}] ${d.vp}  "${d.className}"`)
  console.log(
    `      ${PROP}: ${d.before}（クラスを外しても ${d.after} = このクラスは効いていない）`,
  )
  console.log(`      routes: ${[...d.routes].join(', ')}`)
}
await browser.close()
