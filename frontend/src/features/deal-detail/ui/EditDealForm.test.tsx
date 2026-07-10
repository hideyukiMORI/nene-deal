import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { buildDeal } from '@tests/factories/deal'
import { EditDealForm, type EditDealFormProps } from './EditDealForm'

function baseProps(overrides: Partial<EditDealFormProps> = {}): EditDealFormProps {
  return {
    deal: buildDeal(),
    pending: false,
    errorMessage: null,
    onSubmit: vi.fn(() => Promise.resolve(true)),
    ...overrides,
  }
}

describe('EditDealForm', () => {
  it('displays the amount in whole yen, not raw cents (regression #80)', () => {
    renderWithProviders(
      <EditDealForm {...baseProps({ deal: buildDeal({ amountCents: 62_000_000 }) })} />,
    )

    expect(screen.getByLabelText('Amount (JPY)')).toHaveValue(620_000)
  })

  it('submits the entered yen amount converted to cents (regression #80)', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(
      <EditDealForm {...baseProps({ deal: buildDeal({ amountCents: 62_000_000 }), onSubmit })} />,
    )

    const amount = screen.getByLabelText('Amount (JPY)')
    await user.clear(amount)
    await user.type(amount, '650000')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSubmit).toHaveBeenCalledOnce()
    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ amountCents: 65_000_000 }))
  })

  it('rejects a non-integer yen amount', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<EditDealForm {...baseProps({ onSubmit })} />)

    const amount = screen.getByLabelText('Amount (JPY)')
    await user.clear(amount)
    await user.type(amount, '1234.5')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSubmit).not.toHaveBeenCalled()
    expect(
      screen.getByText('Amount must be a whole number of yen, 0 or greater.'),
    ).toBeInTheDocument()
  })
})
