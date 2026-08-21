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

// #80 was "the form shows and submits raw cents, so every amount is 100x off".
// #81 removed the yen⇔cents boundary entirely (for JPY the minor unit IS the
// yen), which would make a round-trip assertion on a round number tautological.
// The amounts below are deliberately NOT multiples of 100: any re-introduced
// /100 or *100 pair cannot survive them (123_456 / 100 rounds to 1_235, and
// 1_235 * 100 is 123_500), so these stay real guards rather than identities.
describe('EditDealForm', () => {
  it('displays the stored amount as-is, not scaled (regression #80)', () => {
    renderWithProviders(
      <EditDealForm {...baseProps({ deal: buildDeal({ amountCents: 123_456 }) })} />,
    )

    expect(screen.getByLabelText('Amount (JPY)')).toHaveValue(123_456)
  })

  it('submits the entered yen amount unscaled (regression #80)', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(
      <EditDealForm {...baseProps({ deal: buildDeal({ amountCents: 123_456 }), onSubmit })} />,
    )

    const amount = screen.getByLabelText('Amount (JPY)')
    await user.clear(amount)
    await user.type(amount, '654321')
    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSubmit).toHaveBeenCalledOnce()
    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ amountCents: 654_321 }))
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
