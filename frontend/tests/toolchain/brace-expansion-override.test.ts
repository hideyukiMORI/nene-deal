/**
 * Toolchain integrity guard — `overrides` must not break the tools they patch.
 *
 * Why this file exists: on 2026-07-29 a *flat* `"brace-expansion": "^5.0.8"` override was
 * merged to silence GHSA-mh99-v99m-4gvg. It forced every minimatch onto brace-expansion v5,
 * whose entry point changed shape:
 *
 *     v1/v2  module.exports = expand            // callable
 *     v5     module.exports = { expand, ... }   // named exports only
 *
 * minimatch@3 and @5 do `const expand = require('brace-expansion')` and then call it, so they
 * threw `TypeError: expand is not a function` on any pattern containing a brace. Nothing in
 * `npm run check` used a brace pattern, so lint, tests, knip and the Storybook build were all
 * green while eslint-plugin-import, eslint-plugin-jsx-a11y and @redocly/openapi-core were
 * quietly broken. Green CI is not the same as a working toolchain.
 *
 * The guard: resolve every minimatch actually installed and run a brace pattern through it.
 * Any override that reintroduces the incompatibility fails here instead of hiding.
 */
import { readdirSync, statSync } from 'node:fs'
import { createRequire } from 'node:module'
import { dirname, join, relative } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

const frontendRoot = join(dirname(fileURLToPath(import.meta.url)), '..', '..')
const require = createRequire(join(frontendRoot, 'package.json'))

/**
 * Every installed copy of minimatch, found by walking node_modules on disk. Reading the tree
 * directly (rather than parsing `npm ls`) keeps the guard honest about what Node will actually
 * resolve at require time, which is the thing that broke.
 */
function installedMinimatchPaths(dir = join(frontendRoot, 'node_modules'), depth = 0): string[] {
  if (depth > 6) return []
  let entries: string[]
  try {
    entries = readdirSync(dir)
  } catch {
    return []
  }

  const found: string[] = []
  for (const entry of entries) {
    const full = join(dir, entry)
    if (!statSync(full, { throwIfNoEntry: false })?.isDirectory()) continue

    if (entry === 'minimatch') {
      found.push(full)
      continue
    }
    // Recurse into scopes (@scope/pkg) and nested node_modules only — not into package sources.
    if (entry.startsWith('@') || entry === 'node_modules') {
      found.push(...installedMinimatchPaths(full, depth + 1))
    } else {
      const nested = join(full, 'node_modules')
      if (statSync(nested, { throwIfNoEntry: false })?.isDirectory() === true) {
        found.push(...installedMinimatchPaths(nested, depth + 1))
      }
    }
  }
  return found
}

/** minimatch is a bare callable in v3/v5 and a namespace in v10 — accept either shape. */
function asMatcher(mod: unknown): (target: string, pattern: string) => boolean {
  if (typeof mod === 'function') return mod as (target: string, pattern: string) => boolean
  const named = (mod as { minimatch?: unknown }).minimatch
  if (typeof named === 'function') return named as (target: string, pattern: string) => boolean
  throw new Error('minimatch export is neither callable nor a { minimatch } namespace')
}

describe('brace-expansion override compatibility', () => {
  const paths = installedMinimatchPaths()

  it('finds the minimatch copies the toolchain actually loads', () => {
    // If this ever hits 0 the rest of the suite would vacuously pass.
    expect(paths.length).toBeGreaterThan(0)
  })

  it.each(paths)('expands braces through the minimatch at %s', (path) => {
    const where = relative(frontendRoot, path)
    const version = (require(join(path, 'package.json')) as { version: string }).version
    const match = asMatcher(require(path))

    // A brace pattern is the only thing that reaches brace-expansion; a plain glob would
    // pass even with a broken expander, which is exactly how the 2026-07-29 break hid.
    expect(match('abd', 'a{b,c}d'), `minimatch@${version} at ${where}`).toBe(true)
    expect(match('acd', 'a{b,c}d'), `minimatch@${version} at ${where}`).toBe(true)
    expect(match('aed', 'a{b,c}d'), `minimatch@${version} at ${where}`).toBe(false)
  })
})
