/**
 * computed-probe.mjs の before/after を突き合わせる。
 *
 * 判定は「差分 0」だけでは足りない。**踏破数が期待どおりか**も併せて見る:
 * 網が黙って縮むと差分は自動的に 0 になり、それは「変わっていない」ではなく
 * 「測っていない」を意味する（0 件ループは正常終了する）。よって
 *   - before / after のスナップショット集合が一致しているか
 *   - 各スナップショットの要素数が極端に減っていないか
 *   - 陽性対照がすべて live か
 * を FAIL 条件に含める。
 *
 * 使い方: node tools/smoke/computed-diff.mjs before.json after.json
 */
import fs from 'node:fs'

const [beforePath, afterPath] = process.argv.slice(2)
if (!beforePath || !afterPath) {
  console.error('usage: computed-diff.mjs <before.json> <after.json>')
  process.exit(2)
}
const B = JSON.parse(fs.readFileSync(beforePath, 'utf8'))
const A = JSON.parse(fs.readFileSync(afterPath, 'utf8'))

let fail = false

if (B.prop !== A.prop) {
  console.error(`FAIL: 測定プロパティが違う (${B.prop} vs ${A.prop})`)
  fail = true
}

const bKeys = Object.keys(B.acc).sort()
const aKeys = Object.keys(A.acc).sort()
const missing = bKeys.filter((k) => !aKeys.includes(k))
const added = aKeys.filter((k) => !bKeys.includes(k))
if (missing.length || added.length) {
  console.error(
    `FAIL: スナップショット集合が不一致 — 欠落 ${JSON.stringify(missing)} / 追加 ${JSON.stringify(added)}`,
  )
  fail = true
}

let totalB = 0
let totalA = 0
const diffs = []
for (const k of bKeys) {
  if (!A.acc[k]) continue
  const b = B.acc[k]
  const a = A.acc[k]
  totalB += Object.keys(b).length
  totalA += Object.keys(a).length
  const paths = new Set([...Object.keys(b), ...Object.keys(a)])
  for (const p of paths) {
    if (b[p] !== a[p])
      diffs.push({
        snapshot: k,
        path: p,
        before: b[p] ?? '(要素なし)',
        after: a[p] ?? '(要素なし)',
      })
  }
}

console.log(`prop=${A.prop}`)
console.log(`snapshots: ${bKeys.length} (before) / ${aKeys.length} (after)`)
console.log(`値を持つ要素: ${totalB} (before) -> ${totalA} (after)`)

// 測定対象が消えたら「差分0」は無意味。
if (totalA === 0 || totalB === 0) {
  console.error('FAIL: 値を持つ要素が 0 — 何も測れていない。差分 0 を根拠にしてはいけない。')
  fail = true
}
if (totalA < totalB * 0.9) {
  console.error(`FAIL: 測定対象が ${totalB} → ${totalA} へ 10% 超減った。網が縮んだ可能性がある。`)
  fail = true
}

const dead = (A.control || []).filter((c) => !c.found || !c.live)
if (dead.length) {
  console.error(`FAIL: 陽性対照が live でない: ${JSON.stringify(dead)}`)
  fail = true
} else {
  console.log(`陽性対照: ${A.control.length}/${A.control.length} すべて live ✓`)
}

if (diffs.length === 0) {
  console.log('computed 差分: 0 ✓（全経路・全ビューポートで同一要素の値が一致）')
} else {
  console.log(`computed 差分: ${diffs.length} 件`)
  for (const d of diffs.slice(0, 50)) {
    console.log(`  ${d.snapshot}  ${d.path}\n    ${d.before}  ->  ${d.after}`)
  }
  if (diffs.length > 50) console.log(`  ... 他 ${diffs.length - 50} 件`)
  fail = true
}

process.exit(fail ? 1 : 0)
