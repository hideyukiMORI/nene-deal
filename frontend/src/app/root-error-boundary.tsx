import { Button, Stack, Text } from '@hideyukimori/nene2-ui'
import { Component, type ErrorInfo, type ReactNode } from 'react'
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
          {/* gap: local `md` was gap-4 = 16px; the kit's `sm` is 1rem = 16px. The kit's
              `md` is 20px — the scale names shift two steps (#225). */}
          <Stack gap="sm">
            {/* park（板 L15・契約v2第二波 typography）: 見出しの 21px / leading 1.2 /
                tracking -0.015em はいずれも契約語彙に無く、utility 化は arbitrary value
                （R1⑤ 禁止）になる。`.avatar` の 13/18px と同じ「供給待ち」扱い。 */}
            <h1 className="t-h1">Something went wrong / 問題が発生しました</h1>
            <Text tone="muted">An unexpected error occurred. 予期しないエラーが発生しました。</Text>
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
