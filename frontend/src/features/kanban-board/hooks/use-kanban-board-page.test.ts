import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useKanbanBoardPage } from './use-kanban-board-page'

describe('useKanbanBoardPage', () => {
  it('loads board columns from the API', async () => {
    const { result } = renderHookWithProviders(() => useKanbanBoardPage())

    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    expect(result.current.columns.length).toBeGreaterThan(0)
    expect(result.current.columns[0]?.deals[0]?.accountLabel).toBe('Acme Corp')
    expect(result.current.stageOptions.length).toBeGreaterThan(0)
    expect(result.current.forecast?.openDealCount).toBe(1)
  })

  it('creates a deal and reports success', async () => {
    const { result } = renderHookWithProviders(() => useKanbanBoardPage())
    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    let created = false
    await act(async () => {
      created = await result.current.submitCreateDeal({
        accountLabel: 'Globex',
        amountCents: 2_000_000,
        stageRef: 'lead',
        probabilityPercent: 25,
      })
    })

    expect(created).toBe(true)
  })

  it('surfaces a validation error when creation fails', async () => {
    const { result } = renderHookWithProviders(() => useKanbanBoardPage())
    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    let created = true
    await act(async () => {
      created = await result.current.submitCreateDeal({
        accountLabel: '',
        amountCents: 0,
        stageRef: 'lead',
        probabilityPercent: 0,
      })
    })

    expect(created).toBe(false)
    await waitFor(() => {
      expect(result.current.createErrorKey).toBe('common.error.validation')
    })
  })
})
