import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { AppProviders } from '@/app/providers'
import { AppRouter } from '@/app/router'
import { applyLocaleFontFamily, resolveLocale } from '@/shared/i18n'
import { applyStoredTheme } from '@/shared/theme'
import '@/shared/ui/theme/index.css'
import '@/fonts'
// Calm design system (ClaudeDesign spec) — imported last so its tokens and
// component classes are authoritative over the legacy Tailwind theme.
import '@/app/design/styles.css'
import '@/app/design/designs.css'

// FOUC guard: detect the locale and apply its font family before React renders.
const storedLocale = (() => {
  try {
    return localStorage.getItem('nene-deal-locale') ?? navigator.language
  } catch {
    return navigator.language
  }
})()
applyLocaleFontFamily(resolveLocale(storedLocale))
applyStoredTheme()

async function enableMocking(): Promise<void> {
  if (import.meta.env.VITE_MOCK_API !== 'true') return
  const { worker } = await import('./mocks/browser')
  await worker.start({ onUnhandledRequest: 'bypass' })
}

const rootElement = document.getElementById('root')
if (rootElement === null) {
  throw new Error('Root element #root not found')
}

void enableMocking().then(() => {
  createRoot(rootElement).render(
    <StrictMode>
      <AppProviders>
        <AppRouter />
      </AppProviders>
    </StrictMode>,
  )
})
