/**
 * 経路別 computed 実測ハーネス — legacy クラス drain の**主証拠**を取る。
 *
 * なぜスクリーンショットでは足りないか（daily 2026-07-30・網の穴3軸）:
 *   - **経路**: `<Stack>` の 9 箇所も `.g1`〜`.g6` の 77 箇所も、その多くは
 *     フォーム/ダイアログ配下にある。ルート遷移しかしない visual-smoke は
 *     これらを**一度も描画しない**。つまり 0px 差分は「変わっていない」では
 *     なく「描画していない」を意味しうる。
 *   - **ビューポート**: `.m-*` は 1024px 未満でしか描画されない（#195）。
 *   - **見えない値**: `cursor` のようにスクショに写らない宣言がある（#196）。
 * そこで **computed 値を経路ごとに直接読む**。visual-smoke は「既存ルートに
 * 回帰が無いこと」を示す従証拠として併走させる。
 *
 * なぜクラス名でセレクトしないか — drain はクラス名そのものを変える
 * （`.g4` → `gap-4`）。クラス基準だと before/after で別物を数えることになり
 * 比較が成立しない。キーは **DOM 構造パス（body からの nth-child 連鎖）**
 * ＝クラス名に依存しない要素の同定子を使う。
 *
 * 使い方:
 *   npm run mock                                  # 別シェルで mock を起動
 *   node tools/smoke/computed-probe.mjs before.json
 *   ...drain を適用...
 *   node tools/smoke/computed-probe.mjs after.json
 *   node tools/smoke/computed-diff.mjs before.json after.json
 *
 * 環境変数:
 *   PROBE_PROP     測るプロパティ（既定 gap）。flex 群なら justify-content 等。
 *   PROBE_CONTROL  陽性対照。**`クラス@プロパティ` の組**（カンマ区切り）。
 *                  既定 gap-1@gap,…,gap-6@gap。`@プロパティ` 省略時は PROBE_PROP。
 *   PROBE_BASE     既定 http://localhost:5187
 *
 * ⚠️ **対照は (クラス, プロパティ) の組で問う。** クラス単独で問うと嘘が出る:
 * `justify-between` は justify-content しか供給しないので、display を測っている
 * 回で「`justify-between` を外しても display が変わらない」のは当たり前であって
 * 「対照が死んでいる」ではない。2026-07-31 に この取り違えを2回踏んだ（liveness
 * 判定と本ツールの両方）。そこで PROBE_PROP に対応しない組は自動で除外し、
 * 対応する組が1つも無ければ**黙って緑にせず FAIL させる**。
 */
import { chromium } from 'playwright'
import fs from 'node:fs'

const BASE = process.env.PROBE_BASE || 'http://localhost:5187'
const PROP = process.env.PROBE_PROP || 'gap'
const CONTROL_SPEC = (process.env.PROBE_CONTROL || 'gap-1,gap-2,gap-3,gap-4,gap-5,gap-6')
  .split(',')
  .map((s) => s.trim())
  .filter(Boolean)
  .map((s) => {
    const [cls, prop = PROP] = s.split('@')
    return { cls, prop }
  })
// 測っているプロパティを供給しない対照は、そもそも問いとして成立しないので落とす。
const CONTROL_CLASSES = CONTROL_SPEC.filter((c) => c.prop === PROP).map((c) => c.cls)
if (CONTROL_CLASSES.length === 0) {
  console.error(
    `ERROR: prop=${PROP} に対応する陽性対照が指定されていない（渡されたのは ` +
      `${CONTROL_SPEC.map((c) => `${c.cls}@${c.prop}`).join(',')}）。` +
      `対照の無い測定は「変えたら変わる」を確認できないので緑にしてはいけない。`,
  )
  process.exit(1)
}
const OUT = process.argv[2] || '/tmp/computed-probe.json'
const CREDS = { email: 'operator@nene-deal.test', password: 'password' }

/** 「値を持たない」既定値。これらしか無い要素は情報量ゼロなので落とす。 */
const EMPTY = new Set(['normal', '', 'none', 'auto', 'normal normal'])

const COLLECT = ([prop, empty]) => {
  const pathOf = (el) => {
    const seg = []
    for (let e = el; e && e !== document.body; e = e.parentElement) {
      const p = e.parentElement
      if (!p) break
      seg.unshift(`${e.tagName.toLowerCase()}:${Array.prototype.indexOf.call(p.children, e) + 1}`)
    }
    return seg.join('/')
  }
  const out = {}
  for (const el of document.querySelectorAll('*')) {
    const v = getComputedStyle(el).getPropertyValue(prop)
    if (empty.includes(v)) continue
    out[pathOf(el)] = v
  }
  return out
}

/**
 * 陽性対照: その値の実供給元が本当にこのクラスか。「不変」を受入条件にする前に
 * 「変えたら変わる」を確認する。二重に存在する系の死んでいる側への修正は常に
 * 緑で通る（#181 の --font-sans 是正がまさにそれ）。
 *
 * **ルートごとに走らせて集約する。** 1ルートでしか対照を取らないと、そのルートに
 * 存在しないクラスが「対照できず FAIL」になり、逆に存在するクラスだけで
 * 「対照 OK」と誤認する。判定は全ルートを通じた OR（どこか1つで live なら live）。
 */
const CONTROL = ([classes, prop]) =>
  classes.map((cls) => {
    const el = document.querySelector(`.${cls}`)
    if (!el) return { cls, found: false }
    const before = getComputedStyle(el).getPropertyValue(prop)
    el.classList.remove(cls)
    const after = getComputedStyle(el).getPropertyValue(prop)
    el.classList.add(cls)
    return { cls, found: true, before, after, live: before !== after }
  })

const controlHits = new Map()
const mergeControl = async (page, route) => {
  for (const c of await page.evaluate(CONTROL, [CONTROL_CLASSES, PROP])) {
    if (!c.found) continue
    const prev = controlHits.get(c.cls)
    // live な観測を優先して残す（dead な経路が1つあっても、供給元である経路が
    // 1つでもあれば「このクラスは効いている」と言える）。
    if (!prev || (!prev.live && c.live)) controlHits.set(c.cls, { ...c, route })
  }
}

const snap = async (page, name, acc) => {
  for (const [vp, width, height] of [
    ['w1280', 1280, 900],
    ['w390', 390, 844],
  ]) {
    await page.setViewportSize({ width, height })
    await page.waitForTimeout(150)
    acc[`${name}__${vp}`] = await page.evaluate(COLLECT, [PROP, [...EMPTY]])
    await mergeControl(page, `${name}__${vp}`)
  }
  await page.setViewportSize({ width: 1280, height: 900 })
  await page.waitForTimeout(100)
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
const acc = {}
const opened = {}

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
await snap(page, '1-login', acc)

await page.fill('input[type="email"], input[name="email"]', CREDS.email)
await page.fill('input[type="password"], input[name="password"]', CREDS.password)
await page.click('button[type="submit"]')
await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 8000 }).catch(() => {})
await page.waitForTimeout(700)
await snap(page, '2-board', acc)

// ── 経路: フォームを開いた状態（visual-smoke が一度も描画しない面） ──
opened['create-deal'] = await clickBtn(page, /Add deal|ディールを追加/)
if (opened['create-deal']) await snap(page, '2b-board-createdeal', acc)

opened['deal-detail'] = await clickBtn(page, /Details|詳細/)
if (opened['deal-detail']) {
  await snap(page, '3-deal-detail', acc) // EditDealForm は常時 DOM 内
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
    await snap(page, name, acc)
    if (formRe && (await clickBtn(page, formRe))) {
      opened[name] = true
      await snap(page, `${name}-form`, acc)
    }
  } else console.log(`note: ${name} not reached`)
}
// 網が黙って縮むのを防ぐ。0 件ループは正常終了するので、踏破数を期待数と照合する。
if (reached !== ADMIN.length) {
  console.error(
    `ERROR: admin ${reached}/${ADMIN.length} — 網が不完全。この結果で parity を主張してはいけない。`,
  )
  await browser.close()
  process.exit(1)
}

const control = CONTROL_CLASSES.map((cls) => controlHits.get(cls) ?? { cls, found: false })

fs.writeFileSync(OUT, JSON.stringify({ prop: PROP, acc, control, opened, reached }, null, 2))
const total = Object.values(acc).reduce((n, m) => n + Object.keys(m).length, 0)
console.log(`prop=${PROP} snapshots=${Object.keys(acc).length} elements=${total} -> ${OUT}`)
console.log('opened:', opened)
for (const c of control) {
  console.log(
    c.found
      ? `control ${c.cls}: ${c.before} -> ${c.after} ${c.live ? '✓ live' : '✗ DEAD（全経路で別ルールが勝っている）'} @${c.route}`
      : `control ${c.cls}: 全経路の DOM に存在しない`,
  )
}
await browser.close()
