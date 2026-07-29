import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { LoginView } from './LoginView'

describe('LoginView', () => {
  it('renders the email and password fields', () => {
    renderWithProviders(
      <LoginView pending={false} errorMessage={null} onSubmit={() => Promise.resolve(true)} />,
    )

    expect(screen.getByRole('heading', { name: 'Sign in' })).toBeInTheDocument()
    expect(screen.getByLabelText('Email')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
  })

  it('submits entered credentials', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<LoginView pending={false} errorMessage={null} onSubmit={onSubmit} />)

    await user.type(screen.getByLabelText('Email'), 'operator@nene-deal.test')
    await user.type(screen.getByLabelText('Password'), 'password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(onSubmit).toHaveBeenCalledWith({
      email: 'operator@nene-deal.test',
      password: 'password',
    })
  })

  // C5 W3 #169 A family drain guard. This test deliberately inspects class
  // attributes — the thing Testing Library tells you not to assert on — because
  // what it guards IS the implementation detail: that the legacy stylesheet
  // classes stay deleted and the token utilities that replaced them stay put.
  // A user-visible assertion cannot see the difference (jsdom applies no CSS),
  // so the container queries below are the only way to catch a silent revert.
  // Both halves are required: the negative half alone passes on a blank render,
  // and the positive half alone would not notice `auth-card` creeping back in
  // alongside the utilities.
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('renders the auth card with token utilities and no drained legacy classes', () => {
    const { container } = renderWithProviders(
      <LoginView pending={false} errorMessage={null} onSubmit={() => Promise.resolve(true)} />,
    )

    const form = container.querySelector('form')
    expect(form).not.toBeNull()
    // positive: the replacement utilities are actually on the element
    expect(form).toHaveClass('bg-surface-raised', 'rounded-lg', 'p-8', 'max-w-sm')
    // negative: the drained legacy classes are gone
    expect(form).not.toHaveClass('auth-card')
    expect(container.querySelector('.auth-form')).toBeNull()
    expect(container.querySelector('.fs-wrap')).toBeNull()
    expect(container.querySelector('.fs-lang')).toBeNull()
    expect(container.querySelector('.brand-logo')).toBeNull()
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */

  it('shows an error message', () => {
    renderWithProviders(
      <LoginView
        pending={false}
        errorMessage="Invalid email or password."
        onSubmit={() => Promise.resolve(false)}
      />,
    )

    expect(screen.getByText('Invalid email or password.')).toBeInTheDocument()
  })
})
