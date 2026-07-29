import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { buildKanbanColumn } from '@tests/factories/board'
import { buildForecastSummary } from '@tests/factories/forecast'
import { KanbanBoardView, type KanbanBoardViewProps } from './KanbanBoardView'

// jsdom navigator.language is en-US, so the I18nProvider resolves to English.
function baseProps(overrides: Partial<KanbanBoardViewProps> = {}): KanbanBoardViewProps {
  return {
    status: 'ready',
    errorMessageKey: null,
    columns: [],
    forecast: null,
    stageOptions: [{ value: 'lead', label: 'Lead' }],
    includeTerminal: false,
    toggleTerminal: vi.fn(),
    includeDeleted: false,
    toggleDeleted: vi.fn(),
    retry: vi.fn(),
    submitCreateDeal: () => Promise.resolve(true),
    createPending: false,
    createErrorKey: null,
    moveDeal: () => Promise.resolve(),
    restoreDeal: () => Promise.resolve(),
    onOpenDeal: vi.fn(),
    ...overrides,
  }
}

describe('KanbanBoardView', () => {
  it('renders the loading state', () => {
    renderWithProviders(<KanbanBoardView {...baseProps({ status: 'loading' })} />)
    expect(screen.getByText('Loading the board…')).toBeInTheDocument()
  })

  it('renders the empty state when there are no columns', () => {
    renderWithProviders(<KanbanBoardView {...baseProps({ status: 'ready', columns: [] })} />)
    expect(screen.getByRole('heading', { name: 'No stages yet' })).toBeInTheDocument()
  })

  it('renders the error state with a retry action', () => {
    renderWithProviders(
      <KanbanBoardView
        {...baseProps({ status: 'error', errorMessageKey: 'common.error.serverError' })}
      />,
    )
    expect(screen.getByRole('heading', { name: 'Could not load the board' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Retry' })).toBeInTheDocument()
  })

  it('renders columns, deals and the forecast summary', () => {
    renderWithProviders(
      <KanbanBoardView
        {...baseProps({ columns: [buildKanbanColumn()], forecast: buildForecastSummary() })}
      />,
    )
    expect(screen.getByRole('heading', { name: 'Lead' })).toBeInTheDocument()
    expect(screen.getByText('Acme Corp')).toBeInTheDocument()
    expect(screen.getByLabelText('This month’s forecast')).toBeInTheDocument()
  })

  it('opens the create-deal form when the add button is clicked', async () => {
    const user = userEvent.setup()
    renderWithProviders(<KanbanBoardView {...baseProps()} />)

    expect(screen.queryByLabelText('Account')).not.toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Add deal' }))
    expect(screen.getByLabelText('Account')).toBeInTheDocument()
  })

  it('invokes onOpenDeal from a card details action', async () => {
    const user = userEvent.setup()
    const onOpenDeal = vi.fn()
    renderWithProviders(
      <KanbanBoardView {...baseProps({ columns: [buildKanbanColumn()], onOpenDeal })} />,
    )

    await user.click(screen.getByRole('button', { name: 'Details' }))
    expect(onOpenDeal).toHaveBeenCalledWith('01DEALACME000000000000000A')
  })

  // C5 W3 #169 B family wave 1. The `data-details-link` half is not cosmetic:
  // use-kanban-dnd does `closest('[data-details-link]')` to let a details click
  // through instead of starting a drag. When that hook was the `.details-link`
  // CLASS, draining the class would have silently re-armed drag-on-click — a
  // runtime-only break no styling assertion would catch.
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('keeps the DnD click-through hook and drops the drained legacy classes', () => {
    const { container } = renderWithProviders(
      <KanbanBoardView {...baseProps({ columns: [buildKanbanColumn()] })} />,
    )

    // positive: the behavioural hook the DnD lookup depends on
    expect(container.querySelector('[data-details-link]')).not.toBeNull()
    // positive: the replacement utilities are on the probability bar
    expect(container.querySelector('.bg-accent')).not.toBeNull()
    // negative: every class drained in this wave is gone
    for (const drained of [
      'details-link',
      'prob',
      'prob-track',
      'prob-fill',
      'deal-owner',
      'deal-foot',
      'col-header',
      'col-meta',
      'board-tools',
    ]) {
      expect(container.querySelector(`.${drained}`)).toBeNull()
    }
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */
})
