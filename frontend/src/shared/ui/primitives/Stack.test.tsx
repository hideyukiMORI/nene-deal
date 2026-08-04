import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { Stack } from './Stack'

/**
 * C5 W3 #169 G family flex-b drain guard.
 *
 * `<Stack>` is the only call site of `.stack` / `.row` that is generated from a
 * ternary rather than a className literal, so a repo-wide grep for the drained
 * tokens cannot see it. It is also invisible to the visual-smoke harness: all
 * ten `<Stack>` usages live inside forms, dialogs and the root error boundary,
 * none of which the route-transition matrix renders (daily 2026-07-30, 網の穴
 * 「経路」). Without this guard the primitive could silently regress to the
 * legacy classes while every screenshot stayed 0px.
 */
describe('Stack — flex-b drain guard', () => {
  it('emits flex utilities for the vertical direction, not the drained .stack', () => {
    const { container } = renderWithProviders(
      <Stack gap="md">
        <span>child</span>
      </Stack>,
    )

    const root = container.firstElementChild
    // positive: the replacement utilities
    expect(root).toHaveClass('flex')
    expect(root).toHaveClass('flex-col')
    expect(root).toHaveClass('gap-4')
    // negative: the drained legacy token is gone
    expect(root).not.toHaveClass('stack')
  })

  it('emits flex utilities for the horizontal direction, not the parked .row', () => {
    const { container } = renderWithProviders(
      <Stack direction="horizontal" gap="sm">
        <span>child</span>
      </Stack>,
    )

    const root = container.firstElementChild
    expect(root).toHaveClass('flex')
    expect(root).toHaveClass('items-center')
    expect(root).toHaveClass('gap-2')
    // `.row` keeps a base rule for six parked call sites; the primitive must
    // not be one of them, or the park would silently grow.
    expect(root).not.toHaveClass('row')
    // `flex-direction: row` was deleted outright (live 0 / 174 observations —
    // it only ever restated the CSS initial value), so no `flex-row` here.
    expect(root?.className).not.toMatch(/\bflex-row\b/)
  })
})
