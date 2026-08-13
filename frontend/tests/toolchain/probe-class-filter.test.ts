/**
 * Guards the liveness probe against **ill-formed questions**.
 *
 * `class-liveness.mjs` decides park/drain by removing a class and watching a
 * computed property. If the class never declared that property, "nothing
 * changed" is not evidence that another rule wins — it is evidence that the
 * question was meaningless. On 2026-08-13 the typography wave asked `.t-cap`
 * and `.t-tiny` (which declare `font-size` only) about `font-weight` and
 * `letter-spacing`, and 24 invalid questions came back as a park table (#208).
 *
 * Both controls are required here, and the second one is the point:
 *
 *   1. the filter drops pairs it can prove are unrelated, and
 *   2. it does **not** drop the rest.
 *
 * A "drop everything" implementation passes (1) on its own — which is exactly
 * why (1) alone is not an acceptance. Positive controls show the instrument can
 * detect a change; they say nothing about whether the question is well formed
 * (判例41).
 */
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import {
  affects,
  declaredPropsByClass,
  filterProbeClasses,
} from '../../tools/smoke/declared-props.mjs'

const frontendRoot = join(dirname(fileURLToPath(import.meta.url)), '..', '..')
const read = (p: string) => readFileSync(join(frontendRoot, p), 'utf8')

const LEGACY_FILES = ['src/app/design/designs.css', 'src/app/design/styles.css']

/** The real ledger CSS, parsed the same way the probe parses it. */
function legacyDeclarations(): Map<string, Set<string>> {
  const acc = new Map<string, Set<string>>()
  for (const file of LEGACY_FILES) declaredPropsByClass(read(file), acc)
  return acc
}

const filter = (classes: string[], prop: string) =>
  filterProbeClasses({ classes, prop, declared: legacyDeclarations() })

describe('probe class filter — it works (positive control)', () => {
  it('drops the pairs that produced the 2026-08-13 phantom DEAD table', () => {
    // Measured, not assumed: these classes declare font-size and nothing else,
    // so a font-weight probe over them can only ever answer "unchanged".
    for (const prop of ['font-weight', 'letter-spacing']) {
      const { kept, dropped } = filter(['t-cap', 't-tiny'], prop)
      expect(dropped.map((d) => d.cls)).toEqual(['t-cap', 't-tiny'])
      expect(dropped.every((d) => d.declares.includes('font-size'))).toBe(true)
      expect(kept).toEqual([])
    }
  })

  it('reports what it dropped, with the properties the class does declare', () => {
    const { dropped } = filter(['t-cap'], 'font-weight')
    // Silent shrinkage is the failure mode being guarded: a probe that quietly
    // measures fewer classes still exits 0 and still prints a table.
    expect(dropped).toEqual([{ cls: 't-cap', declares: ['font-size'] }])
  })

  it('the harness itself refuses to run when every requested pair is invalid', () => {
    // End-to-end over the real script: the filter runs before the browser
    // launches, so an all-invalid request must exit 1 without measuring
    // anything. This is what pins the wiring — the module can be correct while
    // nothing calls it.
    const run = () =>
      execFileSync('node', ['tools/smoke/class-liveness.mjs'], {
        cwd: frontendRoot,
        env: { ...process.env, PROBE_CLASSES: 't-cap,t-tiny', PROBE_PROP: 'font-weight' },
        encoding: 'utf8',
        stdio: 'pipe',
      })
    expect(run).toThrow(/対象クラスが 1 つも無い/)
  })
})

describe('probe class filter — it is not over-eager (negative control)', () => {
  it('keeps the pairs the classes really do supply', () => {
    expect(filter(['t-cap', 't-tiny'], 'font-size').dropped).toEqual([])
    // The colour/weight wave that comes next: .muted/.faint declare colour,
    // .medium/.semi declare weight. Dropping these would silence the wave.
    expect(filter(['muted', 'faint'], 'color').dropped).toEqual([])
    expect(filter(['medium', 'semi'], 'font-weight').dropped).toEqual([])
  })

  it('keeps a class whose shorthand supplies the property', () => {
    const declared = declaredPropsByClass('.short { font: 600 12px/1.4 sans-serif; }')
    const { kept, dropped } = filterProbeClasses({
      classes: ['short'],
      prop: 'font-weight',
      declared,
    })
    expect(dropped).toEqual([])
    expect(kept[0]).toMatchObject({ cls: 'short', reason: 'declares', via: ['font'] })
  })

  it('keeps a class that only declares custom properties', () => {
    // `.a { --gap: 8px }` plus `.b { gap: var(--gap) }` elsewhere: removing the
    // class does move `gap`, even though the class never names it.
    const declared = declaredPropsByClass('.tokens { --gap: 8px; }')
    expect(filterProbeClasses({ classes: ['tokens'], prop: 'gap', declared }).dropped).toEqual([])
  })

  it('keeps a class that authored CSS never mentions', () => {
    // Tailwind utilities and JS-applied classes are not in `src/**/*.css`.
    // Absent evidence is "unknown", not "unrelated" — dropping these would make
    // the utility side of a drain unmeasurable.
    const { kept, dropped } = filter(['gap-4'], 'gap')
    expect(dropped).toEqual([])
    expect(kept[0]).toEqual({ cls: 'gap-4', reason: 'not-in-authored-css' })
  })

  it('relates shorthands and longhands in both directions', () => {
    expect(affects('font', 'font-weight')).toBe(true)
    expect(affects('row-gap', 'gap')).toBe(true) // longhand moves the shorthand's computed value
    expect(affects('overflow', 'overflow-x')).toBe(true) // by name, no table entry needed
    expect(affects('all', 'anything')).toBe(true)
    expect(affects('--accent', 'color')).toBe(true)
    expect(affects('font-size', 'font-weight')).toBe(false) // …but not everything is related
  })

  it('attributes a rule to every class in its selector', () => {
    // `:where(.list-row) .stack .medium { overflow… }` is a real selector in
    // designs.css. A probe of `.list-row` for overflow is a legitimate question;
    // narrowing attribution to the subject alone would drop it.
    const declared = declaredPropsByClass(':where(.list-row) .stack .medium { overflow: hidden; }')
    expect(
      filterProbeClasses({ classes: ['list-row'], prop: 'overflow', declared }).dropped,
    ).toEqual([])
  })
})
