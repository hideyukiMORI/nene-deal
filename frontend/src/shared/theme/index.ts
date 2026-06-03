/**
 * Theme (color mode) — `data-theme` on <html>, light default with a dark
 * toggle. Persisted to localStorage. The Calm design system reads the active
 * theme's tokens; `data-design` stays `calm`.
 */
import { useSyncExternalStore } from 'react'

export type ThemeMode = 'light' | 'dark'

const STORAGE_KEY = 'nene-deal-theme'
const listeners = new Set<() => void>()

function read(): ThemeMode {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === 'light' || stored === 'dark') return stored
  } catch {
    /* ignore */
  }
  return 'light'
}

/** Apply the stored theme to <html> before React renders (FOUC guard). */
export function applyStoredTheme(): void {
  document.documentElement.setAttribute('data-theme', read())
  document.documentElement.setAttribute('data-design', 'calm')
}

export function setTheme(mode: ThemeMode): void {
  document.documentElement.setAttribute('data-theme', mode)
  try {
    localStorage.setItem(STORAGE_KEY, mode)
  } catch {
    /* ignore */
  }
  listeners.forEach((fn) => {
    fn()
  })
}

function getSnapshot(): ThemeMode {
  const attr = document.documentElement.getAttribute('data-theme')
  return attr === 'dark' ? 'dark' : 'light'
}

function subscribe(fn: () => void): () => void {
  listeners.add(fn)
  return () => {
    listeners.delete(fn)
  }
}

/** React binding for the current theme mode. */
export function useTheme(): { theme: ThemeMode; setTheme: (mode: ThemeMode) => void } {
  const theme = useSyncExternalStore(subscribe, getSnapshot, (): ThemeMode => 'light')
  return { theme, setTheme }
}
