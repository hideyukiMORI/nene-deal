import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { EmptyState } from './EmptyState'

/**
 * C5 W3 #169 G family flex-b drain guard.
 *
 * `EmptyState` only renders when a list comes back empty, which the seeded mock
 * never does — so the computed-probe run that proved this wave (0 diff across
 * 20 snapshots) never rendered this element. The drain is safe here by cascade
 * argument rather than measurement: `.empty` declares no `display`, so `.stack`
 * really was the supplier. This test is what keeps that argument honest.
 */
/* eslint-disable testing-library/no-node-access */
describe('EmptyState — flex-b drain guard', () => {
  it('uses the flex utilities instead of the drained .stack', () => {
    const { container } = renderWithProviders(<EmptyState title="none yet" />)

    const root = container.firstElementChild
    expect(root).toHaveClass('empty')
    expect(root).toHaveClass('flex')
    expect(root).toHaveClass('flex-col')
    expect(root).not.toHaveClass('stack')
  })

  it('still renders its title', () => {
    renderWithProviders(<EmptyState title="none yet" />)

    expect(screen.getByText('none yet')).toBeInTheDocument()
  })
})
/* eslint-enable testing-library/no-node-access */
