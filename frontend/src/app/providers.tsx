import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { AppError } from '@/shared/api/client'
import { I18nProvider, useTranslation } from '@/shared/i18n'
import { ToastProvider } from '@hideyukimori/nene2-ui'
import { RootErrorBoundary } from './root-error-boundary'

/**
 * App-layer adapter: resolves the toast surface labels from i18n and mounts the kit's
 * provider. Lives here (app) because the mount point is app-owned and app may depend on
 * i18n (R1②) — the kit ships no strings.
 *
 * 🔴 It **wraps** the tree now rather than sitting beside it. The old `Toaster` was a
 * presentation-only sibling reading a module-level store; the kit's provider hands the queue
 * down through context, so anything calling `useToast` has to be inside it. Left as a
 * sibling it would compile, render nothing, and throw at the first toast.
 *
 * `defaultDurationMs` is not passed: the kit's 5s stands, replacing deal's 2.6s (#225).
 */
function ToastHost({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  return (
    <ToastProvider regionLabel={t('toast.region')} dismissLabel={t('toast.dismiss')}>
      {children}
    </ToastProvider>
  )
}

function createAppQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: (failureCount, error) =>
          failureCount < 2 && error instanceof AppError && error.isRetryable,
        refetchOnWindowFocus: import.meta.env.PROD,
      },
      mutations: {
        retry: false,
      },
    },
  })
}

export function AppProviders({ children }: { children: ReactNode }) {
  const [queryClient] = useState(createAppQueryClient)

  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <ToastHost>
          <RootErrorBoundary>{children}</RootErrorBoundary>
        </ToastHost>
      </QueryClientProvider>
    </I18nProvider>
  )
}
