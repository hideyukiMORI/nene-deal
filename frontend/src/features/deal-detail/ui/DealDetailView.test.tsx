import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { buildDeal } from '@tests/factories/deal'
import { DealDetailView, type DealDetailViewProps } from './DealDetailView'

function baseProps(overrides: Partial<DealDetailViewProps> = {}): DealDetailViewProps {
  return {
    status: 'ready',
    errorMessageKey: null,
    deal: buildDeal(),
    onBack: vi.fn(),
    onDeleted: vi.fn(),
    submitEdit: () => Promise.resolve(true),
    editPending: false,
    editErrorKey: null,
    handoff: () => Promise.resolve(true),
    handoffPending: false,
    handoffErrorKey: null,
    handoffResult: null,
    stages: [],
    changeStage: () => Promise.resolve(true),
    stagePending: false,
    deleteDeal: () => Promise.resolve(true),
    deletePending: false,
    activity: [],
    ...overrides,
  }
}

describe('DealDetailView', () => {
  it('renders the deal, edit form and a Send to Invoice action', () => {
    renderWithProviders(<DealDetailView {...baseProps()} />)

    expect(screen.getByRole('heading', { name: 'Acme Corp' })).toBeInTheDocument()
    expect(screen.getByLabelText('Account')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Send to Invoice' })).toBeInTheDocument()
  })

  it('shows the linked state when the deal is already handed off', () => {
    renderWithProviders(
      <DealDetailView
        {...baseProps({ deal: buildDeal({ invoiceClientId: 4821, invoiceQuoteId: 9930 }) })}
      />,
    )

    expect(screen.getByText('Linked to Invoice')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Send to Invoice' })).not.toBeInTheDocument()
  })

  it('renders the missing state', () => {
    renderWithProviders(<DealDetailView {...baseProps({ status: 'missing', deal: null })} />)
    expect(screen.getByRole('heading', { name: 'Deal not found.' })).toBeInTheDocument()
  })

  it('invokes the handoff action', async () => {
    const user = userEvent.setup()
    const handoff = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<DealDetailView {...baseProps({ handoff })} />)

    await user.click(screen.getByRole('button', { name: 'Send to Invoice' }))
    expect(handoff).toHaveBeenCalledOnce()
  })
})
