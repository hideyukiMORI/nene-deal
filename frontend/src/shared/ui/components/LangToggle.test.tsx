import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { LangToggle } from './LangToggle'

describe('LangToggle', () => {
  it('marks the active locale and fires onSelect for another item', async () => {
    const user = userEvent.setup()
    const onSelect = vi.fn()
    render(
      <LangToggle
        items={[
          { id: 'ja', label: '日本語' },
          { id: 'en', label: 'EN' },
        ]}
        activeId="ja"
        onSelect={onSelect}
        groupLabel="Language"
      />,
    )

    const ja = screen.getByRole('button', { name: '日本語' })
    const en = screen.getByRole('button', { name: 'EN' })
    expect(ja).toHaveAttribute('aria-pressed', 'true')
    expect(en).toHaveAttribute('aria-pressed', 'false')

    await user.click(en)
    expect(onSelect).toHaveBeenCalledWith('en')
  })
})
