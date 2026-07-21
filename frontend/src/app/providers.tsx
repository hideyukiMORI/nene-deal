import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { AppError } from '@/shared/api/client'
import { I18nProvider } from '@/shared/i18n'
import { Toaster } from '@/shared/ui/toast/Toaster'
import { RootErrorBoundary } from './root-error-boundary'

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
        <RootErrorBoundary>{children}</RootErrorBoundary>
        <Toaster />
      </QueryClientProvider>
    </I18nProvider>
  )
}
