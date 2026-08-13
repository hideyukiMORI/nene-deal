/**
 * 「そのクラスは、そのプロパティを供給しうるか」を authored CSS から導出する。
 *
 * liveness 判定は「クラスを外して computed が変わるか」で park を決める。だが
 * **クラスがそのプロパティを宣言していなければ、外して変わらないのは当たり前**で、
 * その `DEAD` は「別ルールが勝っている」ではなく「問いが成立していない」を意味する。
 *
 * 2026-08-13（#169 typography 波）に実際に起きた: `.t-cap` / `.t-tiny` は
 * `font-size` しか宣言していないのに `font-weight` と `letter-spacing` でも
 * 問い合わせ、「DEAD 28/28」「DEAD 62/62」という**無意味な答え**が park 判定の
 * 表になって返ってきた。無効な問いを 24 個投げていた（#208）。
 *
 * 判例41: **陽性対照は「器械が変化を検出できること」を示すだけで、「問いが
 * well-formed であること」は示さない。** 対照は 07-31 に直っていた（computed-probe
 * の `PROBE_CONTROL` は `クラス@プロパティ` の組を要求する）。素通りだったのは
 * 測定対象側だった。
 *
 * ## 落とす方向には倒さない
 *
 * このフィルタは **「無関係だと証明できた組」だけ**を落とす。判定材料は authored
 * CSS のみなので、そこに書かれていないことは「無関係」ではなく「不明」である:
 *
 *   - authored CSS に存在しないクラス（Tailwind が生成する utility 等）→ **残す**
 *   - custom property（`--x`）を宣言しているクラス → 任意のプロパティへ波及しうるので **残す**
 *   - shorthand ⇄ longhand（`font` は `font-weight` を供給する）→ **残す**
 *
 * 落とすだけなら「全部落とす」実装でも陽性対照は通る。効きすぎていないことの
 * 証明とセットでなければ受入にならない（tests/toolchain/probe-class-filter.test.ts）。
 */
import fs from 'node:fs'
import path from 'node:path'
import postcss from 'postcss'

/** セレクタ中のクラス名。`:where(.a) .b > .c` のような入れ子も素直に拾える。 */
const CLASS_IN_SELECTOR = /\.(-?[_a-zA-Z][\w-]*)/g

/**
 * shorthand → longhand。**双方向に効かせる**ので片側だけ書けばよい:
 * `font: …` は `font-weight` を供給し、逆に `row-gap: …` の有無は computed の
 * `gap` を動かす。
 *
 * 網羅表ではない。ここに無い関係は下の prefix 規則（`overflow` ⇄ `overflow-x`）で
 * 拾い、それでも拾えなければ「不明＝残す」に倒れる。**取りこぼしても過剰除去には
 * ならない**向きに設計してある。
 */
const SHORTHANDS = {
  animation: [
    'animation-name',
    'animation-duration',
    'animation-timing-function',
    'animation-delay',
  ],
  background: ['background-color', 'background-image', 'background-position', 'background-size'],
  border: ['border-width', 'border-style', 'border-color'],
  'border-radius': [
    'border-top-left-radius',
    'border-top-right-radius',
    'border-bottom-left-radius',
    'border-bottom-right-radius',
  ],
  flex: ['flex-grow', 'flex-shrink', 'flex-basis'],
  'flex-flow': ['flex-direction', 'flex-wrap'],
  font: [
    'font-family',
    'font-size',
    'font-stretch',
    'font-style',
    'font-variant',
    'font-weight',
    'line-height',
  ],
  gap: ['row-gap', 'column-gap'],
  'grid-area': ['grid-row-start', 'grid-row-end', 'grid-column-start', 'grid-column-end'],
  'grid-template': ['grid-template-rows', 'grid-template-columns', 'grid-template-areas'],
  inset: ['top', 'right', 'bottom', 'left'],
  'list-style': ['list-style-type', 'list-style-position', 'list-style-image'],
  margin: ['margin-top', 'margin-right', 'margin-bottom', 'margin-left'],
  outline: ['outline-width', 'outline-style', 'outline-color'],
  padding: ['padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
  'place-content': ['align-content', 'justify-content'],
  'place-items': ['align-items', 'justify-items'],
  'place-self': ['align-self', 'justify-self'],
  'text-decoration': ['text-decoration-line', 'text-decoration-color', 'text-decoration-style'],
  transition: ['transition-property', 'transition-duration', 'transition-timing-function'],
}

const isCustomProperty = (prop) => prop.startsWith('--')

/** `overflow` ⇄ `overflow-x` のような、名前だけで判る包含関係。 */
const relatedByPrefix = (a, b) => a.startsWith(`${b}-`) || b.startsWith(`${a}-`)

/**
 * 宣言されたプロパティ `declared` が、問い合わせ対象 `queried` の computed 値を
 * 動かしうるか。判らないものは `true`（＝残す）へ倒す。
 */
export function affects(declared, queried) {
  if (declared === queried) return true
  // `all: revert` などは全プロパティを動かす。custom property は var() 経由で
  // 任意のプロパティへ波及しうる（`.a { --gap: 8px }` ＋ `.b { gap: var(--gap) }`）。
  if (declared === 'all' || isCustomProperty(declared)) return true
  if (relatedByPrefix(declared, queried)) return true
  return (
    (SHORTHANDS[declared]?.includes(queried) ?? false) ||
    (SHORTHANDS[queried]?.includes(declared) ?? false)
  )
}

/**
 * クラス名 → そのクラスを含むルールが宣言しているプロパティの集合。
 *
 * ルールの帰属は**広めに**取る（`.a .b { gap }` は `.a` にも `.b` にも数える）。
 * liveness は要素からクラスを外す操作なので、祖先側のクラスとして書かれていても
 * 同一要素で両方に一致しうる。狭く取ると正当な問いを落とす向きへ倒れる。
 */
export function declaredPropsByClass(cssText, acc = new Map()) {
  postcss.parse(cssText).walkRules((rule) => {
    const props = []
    // 入れ子ルール（`&:hover { … }`）の宣言も親クラスに数える。
    rule.walkDecls((decl) => props.push(decl.prop))
    if (props.length === 0) return
    for (const match of rule.selector.matchAll(CLASS_IN_SELECTOR)) {
      const cls = match[1]
      if (!acc.has(cls)) acc.set(cls, new Set())
      for (const p of props) acc.get(cls).add(p)
    }
  })
  return acc
}

/** ディレクトリ配下の `.css` を再帰列挙する（authored CSS だけが判定材料）。 */
export function collectCssFiles(dir) {
  const out = []
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) out.push(...collectCssFiles(full))
    else if (entry.isFile() && entry.name.endsWith('.css')) out.push(full)
  }
  return out.sort()
}

/**
 * `classes` を「`prop` を供給しうるもの」だけに絞る。
 *
 * 戻り値の `dropped` は**必ず表示する**こと。静かに縮む網は、縮んだことを
 * 隠したまま緑を返す（0 件ループが正常終了するのと同じ形）。
 */
export function filterProbeClasses({ classes, prop, declared }) {
  const kept = []
  const dropped = []
  for (const cls of classes) {
    const props = declared.get(cls)
    // authored CSS に無いクラスは「無関係」ではなく「判定材料が無い」。utility や
    // JS から付くクラスがここに来るので、落とさずに残す。
    if (!props) {
      kept.push({ cls, reason: 'not-in-authored-css' })
      continue
    }
    const supplying = [...props].filter((p) => affects(p, prop))
    if (supplying.length > 0) kept.push({ cls, reason: 'declares', via: supplying.sort() })
    else dropped.push({ cls, declares: [...props].sort() })
  }
  return { kept, dropped }
}
