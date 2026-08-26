import { useMemo } from 'react'
import { useToast as useKitToast } from '@hideyukimori/nene2-ui'

export interface ToastApi {
  success: (title: string, sub?: string) => void
  error: (title: string, sub?: string) => void
}

/**
 * deal's toast vocabulary, delegating to the kit's queue (#225).
 *
 * 🔴 The wrapper stays; the implementation goes. This is the shape nene-vault used when it
 * moved its i18n onto the kit (`be7e1e4`, C4b): keep the ship's own verbs, hand the machinery
 * upstream. Two reasons it is worth a file:
 *
 * 1. **`success` / `error` say what happened; `show(msg, { tone })` says how it looks.**
 *    Twelve call sites read better for it, and a call site that names a tone is one rename
 *    away from naming a colour.
 * 2. The kit's `useToast` **throws** outside a provider — deliberately, so a `show()` that
 *    silently does nothing cannot exist. Routing through here keeps that behaviour rather
 *    than papering over it.
 *
 * What the kit now owns, and this file no longer implements:
 * - **The live regions exist before there is anything to announce.** deal's own `Toaster`
 *   returned `null` on an empty queue, so the region was created together with its first
 *   toast — assistive technology has nothing to have been watching, and the toast is on
 *   screen and silent. Four ships shipped that independently; the kit's docstring names deal.
 * - `polite` for `success` / `info`, `assertive` only for `danger`.
 * - The default lifetime, **5s rather than deal's old 2.6s**. Not preserved on purpose: a
 *   toast that vanishes before a screen reader finishes reading it was never delivered.
 */
export function useToast(): ToastApi {
  const { show } = useKitToast()
  return useMemo(
    () => ({
      success: (title: string, sub?: string) =>
        void show(title, { tone: 'success', ...(sub === undefined ? {} : { description: sub }) }),
      error: (title: string, sub?: string) =>
        void show(title, { tone: 'danger', ...(sub === undefined ? {} : { description: sub }) }),
    }),
    [show],
  )
}
