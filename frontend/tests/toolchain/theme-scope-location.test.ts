/**
 * Structural guard — `[data-theme]` scopes live in `themes/*.css`, and the debt
 * ledger does not quietly re-acquire them.
 *
 * Why this file exists: the C5 supply PR (#169) did not *shrink* the
 * `nene2/data-theme-selector-location` lint-baseline from 6 to 0 — it deleted
 * the two entries, because the blocks they grandfathered moved into
 * `src/shared/ui/theme/themes/calm-skin.css` where the rule does not apply. That
 * is a stronger result than a shrink, but it is also a reversible one: nothing
 * stops a later change from writing `[data-theme='dark']` back into
 * `app/design/*.css` and re-registering a baseline to keep CI green. Stylelint
 * would flag the selector, but "add it back to the ledger" is exactly the escape
 * hatch a baseline offers, and the ledger is edited by hand.
 *
 * So the invariant is pinned as a test rather than left to reviewer memory:
 * legacy holds no theme scopes, the theme file holds only tokens, and the two
 * retired baseline entries stay retired.
 *
 * Deliberately reads the files as text. The point is what ships in the
 * repository, not what a CSS-in-JS pipeline could be persuaded to emit.
 */
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import postcss from 'postcss'
import { describe, expect, it } from 'vitest'

const frontendRoot = join(dirname(fileURLToPath(import.meta.url)), '..', '..')
const read = (p: string) => readFileSync(join(frontendRoot, p), 'utf8')

/**
 * Every selector in the file, at any nesting depth.
 *
 * Parsed rather than scanned line-by-line. The first version of this helper
 * collected lines ending in `{`, which silently missed a rule written on one
 * line — `[data-theme='dark'] { --x: 1; }` — i.e. it passed the very negative
 * control it existed to catch. A guard that can be evaded by reformatting is
 * not a guard.
 */
function selectorsOf(css: string): string[] {
  const found: string[] = []
  postcss.parse(css).walkRules((rule) => {
    found.push(rule.selector.replace(/\s+/g, ' ').trim())
  })
  return found
}

/** Declared property names in the file, at any nesting depth. */
function propsOf(css: string): string[] {
  const found: string[] = []
  postcss.parse(css).walkDecls((decl) => {
    found.push(decl.prop)
  })
  return found
}

const LEGACY_FILES = ['src/app/design/designs.css', 'src/app/design/styles.css']
const THEME_FILE = 'src/shared/ui/theme/themes/calm-skin.css'

type RegistryEntry = { kind: string; rule?: string; variant?: string; selector?: string }

/** registries.jsonc as data — comments and trailing commas removed. */
function registryEntries(): RegistryEntry[] {
  const json = read('registries.jsonc')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/^\s*\/\/.*$/gm, '')
    .replace(/,(\s*[}\]])/g, '$1')
  return (JSON.parse(json) as { entries: RegistryEntry[] }).entries
}

describe('theme scope location', () => {
  it.each(LEGACY_FILES)('%s declares no [data-theme] scope', (file) => {
    const offenders = selectorsOf(read(file)).filter((s) => s.includes('[data-theme'))
    expect(offenders).toEqual([])
  })

  it('the calm skin file carries the theme scopes instead', () => {
    const selectors = selectorsOf(read(THEME_FILE))
    expect(selectors).toContain("[data-theme='dark']")
    expect(selectors).toContain("[data-theme='light']")
    expect(selectors).toContain("[data-design='calm'][data-theme='dark']")
    expect(selectors).toContain("[data-design='calm'][data-theme='light']")
  })

  it('the calm skin file declares custom properties only', () => {
    // themes-token-only enforces this in stylelint; asserting it here as well
    // keeps the invariant visible to anyone editing the file without running the
    // full gate, and states plainly why `color-scheme` is not in here.
    const declarations = propsOf(read(THEME_FILE))
    expect(declarations.length).toBeGreaterThan(0)
    expect(declarations.filter((p) => !p.startsWith('--'))).toEqual([])
  })

  it('color-scheme is applied through the token, not a second [data-theme] block', () => {
    expect(read(THEME_FILE)).toContain('--color-scheme: dark;')
    expect(read(THEME_FILE)).toContain('--color-scheme: light;')
    expect(read('src/app/design/styles.css')).toContain('color-scheme: var(--color-scheme);')
  })

  it('no data-theme-selector-location baseline is re-registered', () => {
    // Parsed, not substring-matched: the file *comments on* the retired rule by
    // name to explain why the entries are gone, and a text search cannot tell
    // that apart from a live registration. (Caught by this test on first run.)
    const entries = registryEntries()
    const revived = entries.filter(
      (e) => e.kind === 'lint-baseline' && e.rule === 'nene2/data-theme-selector-location',
    )
    expect(revived).toEqual([])
  })

  it('the composite scopes stay registered as scoped-theme', () => {
    // Removing these makes themes-token-only reject calm-skin.css, so they are
    // load-bearing rather than documentation.
    const selectors = registryEntries()
      .filter((e) => e.kind === 'scoped-theme' && e.variant === 'local')
      .map((e) => e.selector)
    expect(selectors).toContain("[data-design='calm'][data-theme='dark']")
    expect(selectors).toContain("[data-design='calm'][data-theme='light']")
  })
})
