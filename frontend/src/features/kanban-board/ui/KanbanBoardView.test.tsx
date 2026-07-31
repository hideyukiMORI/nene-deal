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
      // wave 1
      'details-link',
      'prob',
      'prob-track',
      'prob-fill',
      'deal-owner',
      'deal-foot',
      'col-header',
      'col-meta',
      'board-tools',
      // wave 2
      'col-name',
      'count',
    ]) {
      expect(container.querySelector(`.${drained}`)).toBeNull()
    }
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */

  // C5 W3 #169 G family spacing-b guard. Three different outcomes share this
  // wave, and each needs its own assertion because reverting any one of them
  // would still pass the other two:
  //
  //  1. migrated  — `.g1`/`.g2`/`.g3`/`.g5`/`.g6` became `gap-*` utilities.
  //  2. removed   — on `.content` the gap was never coming from `.g6`;
  //                 `.shell-calm .content` (0,2,0) outranked it. Moving that
  //                 site to a utility would have *changed* the layout (32px →
  //                 24px), so the class was dropped instead of translated.
  //  3. PARKED    — `.g4` deliberately STAYS on `.page-head`. Below 1024px the
  //                 @media `[data-design='calm'] .page-head` supplies 12px and
  //                 `.g4` is inert; a utility would win at every width and make
  //                 mobile 16px. `max-lg:` cannot express it either (width <
  //                 64rem excludes 1024px itself). The assertion below is a
  //                 guard against a well-meaning future migration.
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('migrates the spacing classes, drops the inert ones and keeps the parked .g4', () => {
    const { container } = renderWithProviders(
      <KanbanBoardView {...baseProps({ columns: [buildKanbanColumn()] })} />,
    )

    // 1. positive: the replacement utilities are on the elements
    const section = container.querySelector('section.content')
    expect(section).not.toBeNull()
    expect(container.querySelector('.gap-1')).not.toBeNull()
    expect(container.querySelector('.gap-4')).not.toBeNull()

    // 2. the inert `.g6` is gone AND was not replaced by a utility — the gap on
    //    `.content` must keep coming from the legacy descendant rule.
    expect(section).not.toHaveClass('g6')
    expect(section?.className).not.toMatch(/\bgap-\d/)

    // 3. park: `.g4` must still be on the page-head row.
    const pageHead = container.querySelector('.page-head')
    expect(pageHead).not.toBeNull()
    expect(pageHead).toHaveClass('g4')

    // negative: every spacing class drained in this wave is gone repo-wide.
    for (const drained of ['g1', 'g2', 'g3', 'g5', 'g6']) {
      expect(container.querySelector(`.${drained}`)).toBeNull()
    }
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */

  // C5 W3 #169 G family flex-a guard. `.between` / `.wrap` / `.grow` each declare
  // exactly one property and nothing in @layer legacy competes for it, so all 74
  // observed (class, property, element) pairs were live and the whole set moved.
  // `.row` and `.stack` are deliberately NOT in this wave: they are entangled with
  // `@media (max-width:1024px)` rules on `.page-head` (flex-direction/align-items)
  // and `.user-ctrls` (display:none), where a utility would win at every width.
  // The assertions below therefore pin both halves — the migrated classes are gone
  // AND the deferred ones are still present, so splitting the wave stays visible.
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('migrates the single-property flex classes and defers .row/.stack', () => {
    const { container } = renderWithProviders(
      <KanbanBoardView {...baseProps({ columns: [buildKanbanColumn()] })} />,
    )

    // positive: the replacement utilities are on the elements
    expect(container.querySelector('.justify-between')).not.toBeNull()
    expect(container.querySelector('.flex-wrap')).not.toBeNull()

    // negative: the drained classes are gone
    for (const drained of ['between', 'wrap', 'grow']) {
      expect(container.querySelector(`.${drained}`)).toBeNull()
    }

    // deferred to flex-b — if these disappear without the @media entanglement
    // being handled, mobile layout breaks silently (see the wave comment above).
    expect(container.querySelector('.row')).not.toBeNull()
    expect(container.querySelector('.stack')).not.toBeNull()
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */
})
