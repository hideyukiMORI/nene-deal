import { Component, type ErrorInfo, type ReactNode } from 'react'
import { Button, Stack, Text } from '@/shared/ui'

interface RootErrorBoundaryProps {
  children: ReactNode
}

interface RootErrorBoundaryState {
  hasError: boolean
}

/**
 * Last-resort boundary for uncaught render errors. Text is intentionally a
 * minimal fixed string: the i18n context itself may be the failure, so this
 * fallback does not depend on it.
 */
export class RootErrorBoundary extends Component<RootErrorBoundaryProps, RootErrorBoundaryState> {
  override state: RootErrorBoundaryState = { hasError: false }

  static getDerivedStateFromError(): RootErrorBoundaryState {
    return { hasError: true }
  }

  override componentDidCatch(error: Error, info: ErrorInfo): void {
    if (import.meta.env.DEV) {
      console.error('Root error boundary caught:', error, info)
    }
  }

  private readonly handleReset = (): void => {
    this.setState({ hasError: false })
    window.location.assign('/')
  }

  override render(): ReactNode {
    if (this.state.hasError) {
      return (
        <main className="mx-auto flex min-h-screen max-w-3xl items-center px-inline-lg py-stack-xl">
          <Stack gap="md">
            <Text as="h1" variant="heading-md">
              Something went wrong / 問題が発生しました
            </Text>
            <Text muted>An unexpected error occurred. 予期しないエラーが発生しました。</Text>
            <Button variant="secondary" onClick={this.handleReset}>
              Reload / 再読み込み
            </Button>
          </Stack>
        </main>
      )
    }

    return this.props.children
  }
}
