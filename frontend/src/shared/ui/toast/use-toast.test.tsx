// @vitest-environment jsdom
/**
 * The seam between deal's toast vocabulary and the kit's queue (#225).
 *
 * 🔴 Tested through the real `ToastProvider`, not against a mocked `show`. A mock would pin
 * the arguments this wrapper passes; what matters is what a person ends up seeing and what a
 * screen reader ends up in. The tone→region mapping in particular is only observable on the
 * far side of the provider: `success` has to land in the polite region, because `assertive`
 * interrupts and a completed save is not worth interrupting for.
 */
import { ToastProvider } from '@hideyukimori/nene2-ui'
import { fireEvent, render, screen, within } from '@testing-library/react'
import { afterEach, describe, expect, it } from 'vitest'
import { useToast } from './use-toast'

afterEach(() => {
  document.body.innerHTML = ''
})

function Trigger({ kind, title, sub }: { kind: 'success' | 'error'; title: string; sub?: string }) {
  const toast = useToast()
  return (
    <button
      onClick={() => {
        toast[kind](title, sub)
      }}
    >
      go
    </button>
  )
}

function mount(ui: React.ReactNode): void {
  render(
    <ToastProvider regionLabel="Notifications" dismissLabel="Dismiss">
      {ui}
    </ToastProvider>,
  )
  // fireEvent は Testing Library 側で act 済みなので包まない（`no-unnecessary-act`）。
  fireEvent.click(screen.getByText('go'))
}

/** The two live regions carry the same accessible name; `aria-live` is what separates them. */
function region(live: 'polite' | 'assertive'): HTMLElement {
  const found = screen
    .getAllByRole('region', { name: 'Notifications' })
    .find((r) => r.getAttribute('aria-live') === live)
  if (found === undefined) throw new Error(`no ${live} live region`)
  return found
}

describe('deal のトースト語彙 → キットのキュー', () => {
  it('success は polite 側に出る（完了の報告で読み上げを中断させない）', () => {
    mount(<Trigger kind="success" title="保存しました" />)
    expect(within(region('polite')).getByText('保存しました')).toBeInTheDocument()
    expect(within(region('assertive')).queryByText('保存しました')).toBeNull()
  })

  it('error は assertive 側に出る（失敗は割り込んでよい）', () => {
    mount(<Trigger kind="error" title="失敗しました" />)
    expect(within(region('assertive')).getByText('失敗しました')).toBeInTheDocument()
    expect(within(region('polite')).queryByText('失敗しました')).toBeNull()
  })

  it('第2引数は二段目として描かれる（deal の success 7箇所は全部これを渡す）', () => {
    mount(<Trigger kind="success" title="移動しました" sub="Acme → 受注" />)
    const polite = within(region('polite'))
    expect(polite.getByText('移動しました')).toBeInTheDocument()
    expect(polite.getByText('Acme → 受注')).toBeInTheDocument()
  })

  it('省略したら二段目は出ない — 一段目だけが残る', () => {
    mount(<Trigger kind="success" title="保存しました" />)
    // 🔴 陽性対照つき: 「二段目が無い」を `queryByText(...)` の null だけで言うと、
    // トースト自体が出ていない場合と区別できない。一段目が在ることを先に確かめる。
    expect(within(region('polite')).getByText('保存しました')).toBeInTheDocument()
    expect(within(region('polite')).queryByText('Acme → 受注')).toBeNull()
  })

  it('provider の外では黙って無視せず throw する（キットの設計・deal の旧実装は無視していた）', () => {
    expect(() => render(<Trigger kind="success" title="x" />)).toThrow(/ToastProvider/)
  })
})
