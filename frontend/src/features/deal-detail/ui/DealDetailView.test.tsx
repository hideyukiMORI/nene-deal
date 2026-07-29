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

  // C5 W3 #169 C family wave 1. The timeline classes were drained to token
  // utilities; .activity/.activity-item/.activity-dot/.tl-gapnote were deleted
  // outright as dead rules (zero grep hits AND zero elements on a page where
  // the timeline is proven to render — the positive control that #178's
  // "unreachable CSS" judgement also rested on).
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('renders the activity timeline with token utilities and no drained classes', () => {
    const { container } = renderWithProviders(
      <DealDetailView
        {...baseProps({
          activity: [
            {
              id: '01HIST00000000000000000001',
              action: 'stage_changed',
              fromStageId: 'proposal',
              toStageId: 'won',
              actorUserId: '01USER0000000000000000000A',
              actorLabel: 'operator@nene-deal.test',
              changes: [],
              createdAt: '2026-05-31 09:15:00',
            },
          ],
        })}
      />,
    )

    // positive: the timeline actually rendered (without this the negative half
    // below would pass on an empty state and prove nothing)
    const item = container.querySelector('.tl-item')
    expect(item).not.toBeNull()
    expect(item?.querySelector('.font-semibold')).not.toBeNull()

    // negative: drained + deleted classes are gone
    for (const drained of [
      'timeline',
      'tl-body',
      'tl-title',
      'tl-meta',
      'activity',
      'activity-item',
      'activity-dot',
      'tl-gapnote',
    ]) {
      expect(container.querySelector(`.${drained}`)).toBeNull()
    }
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */
})
