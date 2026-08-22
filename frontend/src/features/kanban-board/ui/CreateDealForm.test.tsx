import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { CreateDealForm, type CreateDealFormProps } from './CreateDealForm'

function baseProps(overrides: Partial<CreateDealFormProps> = {}): CreateDealFormProps {
  return {
    stageOptions: [{ value: 'lead', label: 'Lead' }],
    pending: false,
    errorMessage: null,
    onSubmit: vi.fn(() => Promise.resolve(true)),
    onCancel: vi.fn(),
    ...overrides,
  }
}

// See EditDealForm.test.tsx for why the amount here is not a multiple of 100.
describe('CreateDealForm', () => {
  it('submits the entered yen amount unscaled (regression #80)', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<CreateDealForm {...baseProps({ onSubmit })} />)

    await user.type(screen.getByLabelText('Account'), 'Acme Corp')
    const amount = screen.getByLabelText('Amount (JPY)')
    await user.clear(amount)
    await user.type(amount, '654321')
    await user.click(screen.getByRole('button', { name: 'Create' }))

    expect(onSubmit).toHaveBeenCalledOnce()
    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ amountCents: 654_321 }))
  })

  it('rejects a non-integer yen amount', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<CreateDealForm {...baseProps({ onSubmit })} />)

    await user.type(screen.getByLabelText('Account'), 'Acme Corp')
    const amount = screen.getByLabelText('Amount (JPY)')
    await user.clear(amount)
    await user.type(amount, '1234.5')
    await user.click(screen.getByRole('button', { name: 'Create' }))

    expect(onSubmit).not.toHaveBeenCalled()
    expect(
      screen.getByText('Amount must be a whole number of yen, 0 or greater.'),
    ).toBeInTheDocument()
  })
})
