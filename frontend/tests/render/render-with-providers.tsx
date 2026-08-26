import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import {
  render,
  renderHook,
  type RenderHookOptions,
  type RenderHookResult,
  type RenderOptions,
  type RenderResult,
} from '@testing-library/react'
import type { ReactElement, ReactNode } from 'react'
import { ToastProvider } from '@hideyukimori/nene2-ui'
import { I18nProvider } from '@/shared/i18n'

/**
 * The app's provider tree, minus the router and the error boundary.
 *
 * 🔴 `ToastProvider` is here because the kit's `useToast` **throws** outside one — on purpose,
 * so a `show()` that silently does nothing cannot exist. deal's previous toast store was
 * module-level and worked with no provider at all, so these tests never needed one; when the
 * queue moved to the kit (#225) five specs failed at once. That is the harness having drifted
 * from the app, not a regression: `providers.tsx` wraps the tree the same way.
 *
 * ⚠️ Keep this in step with `src/app/providers.tsx`. A helper that renders under fewer
 * providers than the app passes tests the app would fail.
 */
function TestProviders({
  children,
  queryClient,
}: {
  children: ReactNode
  queryClient: QueryClient
}) {
  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <ToastProvider regionLabel="Notifications" dismissLabel="Dismiss">
          {children}
        </ToastProvider>
      </QueryClientProvider>
    </I18nProvider>
  )
}

export function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })
}

export function renderWithProviders(ui: ReactElement, options?: RenderOptions): RenderResult {
  const queryClient = createTestQueryClient()

  return render(ui, {
    wrapper: ({ children }) => <TestProviders queryClient={queryClient}>{children}</TestProviders>,
    ...options,
  })
}

/**
 * Render a hook wrapped in the app providers (React Query + i18n). Pairs with
 * MSW handlers to exercise feature hooks at the network boundary.
 */
export function renderHookWithProviders<Result, Props>(
  hook: (initialProps: Props) => Result,
  options?: RenderHookOptions<Props>,
): RenderHookResult<Result, Props> {
  const queryClient = createTestQueryClient()

  return renderHook(hook, {
    wrapper: ({ children }: { children: ReactNode }) => (
      <TestProviders queryClient={queryClient}>{children}</TestProviders>
    ),
    ...options,
  })
}
