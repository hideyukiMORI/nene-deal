import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { ThemeToggle } from './ThemeToggle'

describe('ThemeToggle', () => {
  it('marks the active mode and fires onThemeChange when the other is clicked', async () => {
    const user = userEvent.setup()
    const onThemeChange = vi.fn()
    render(
      <ThemeToggle
        theme="light"
        onThemeChange={onThemeChange}
        groupLabel="Theme"
        lightLabel="Light"
        darkLabel="Dark"
      />,
    )

    const light = screen.getByRole('button', { name: 'Light' })
    const dark = screen.getByRole('button', { name: 'Dark' })
    expect(light).toHaveAttribute('aria-pressed', 'true')
    expect(dark).toHaveAttribute('aria-pressed', 'false')

    await user.click(dark)
    expect(onThemeChange).toHaveBeenCalledWith('dark')
  })
})
