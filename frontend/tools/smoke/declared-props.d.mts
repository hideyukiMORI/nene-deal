/**
 * Types for `declared-props.mjs`. The tool itself stays plain ESM so `node` can
 * run it without a build step; this file exists so the toolchain test can import
 * it from TypeScript without `allowJs`.
 */
export declare function affects(declared: string, queried: string): boolean

export declare function declaredPropsByClass(
  cssText: string,
  acc?: Map<string, Set<string>>,
): Map<string, Set<string>>

export declare function collectCssFiles(dir: string): string[]

export type KeptClass = {
  cls: string
  reason: 'declares' | 'not-in-authored-css'
  via?: string[]
}

export type DroppedClass = {
  cls: string
  declares: string[]
}

export declare function filterProbeClasses(input: {
  classes: string[]
  prop: string
  declared: Map<string, Set<string>>
}): { kept: KeptClass[]; dropped: DroppedClass[] }
