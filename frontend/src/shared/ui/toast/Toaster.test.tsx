import { act, fireEvent, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from './Toaster'
import { dismissToast, pushToast } from './toast-store'

afterEach(() => {
  vi.useRealTimers()
})

describe('Toaster', () => {
  it('renders nothing until a toast is raised', () => {
    render(<Toaster regionLabel="Notifications" dismissLabel="Dismiss" />)
    expect(screen.queryByRole('region', { name: 'Notifications' })).not.toBeInTheDocument()
  })

  it('shows a raised toast and dismisses it on click', () => {
    vi.useFakeTimers()
    render(<Toaster regionLabel="Notifications" dismissLabel="Dismiss" />)

    let id = 0
    act(() => {
      id = pushToast('Saved', 'All changes stored', 'success')
    })

    const region = screen.getByRole('region', { name: 'Notifications' })
    expect(region).toBeInTheDocument()
    expect(screen.getByText('Saved')).toBeInTheDocument()
    expect(screen.getByText('All changes stored')).toBeInTheDocument()

    // Click the toast (aria-labelled by the dismiss label) → begins the leave
    // animation, then removes after the leave delay.
    fireEvent.click(screen.getByRole('button', { name: 'Dismiss' }))
    act(() => {
      vi.advanceTimersByTime(400)
    })
    expect(screen.queryByText('Saved')).not.toBeInTheDocument()

    dismissToast(id)
  })
})
