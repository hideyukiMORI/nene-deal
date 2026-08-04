import { screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { NotFoundPage } from './NotFoundPage'

// NotFoundPage renders a <Link>, which needs a router context of its own —
// renderWithProviders supplies i18n + React Query but deliberately not routing.
const renderPage = () =>
  renderWithProviders(
    <MemoryRouter>
      <NotFoundPage />
    </MemoryRouter>,
  )

describe('NotFoundPage', () => {
  it('renders the 404 code and a way back', () => {
    renderPage()

    expect(screen.getByText('404')).toBeInTheDocument()
    expect(screen.getByRole('link')).toHaveAttribute('href', '/')
  })

  // C5 W3 #169 A family drain guard — .fs-wrap was shared with LoginView, so
  // the class could only be removed from the stylesheet once BOTH call sites
  // moved. This is the second call site; without it the drain is unguarded here.
  // Asserts on class attributes for the same reason as the LoginView guard: the
  // regression it catches is invisible to a user-facing query under jsdom.
  /* eslint-disable testing-library/no-container, testing-library/no-node-access */
  it('uses the min-h-screen utility instead of the drained .fs-wrap', () => {
    const { container } = renderPage()

    const root = container.firstElementChild
    expect(root).toHaveClass('min-h-screen')
    expect(container.querySelector('.fs-wrap')).toBeNull()
  })

  // C5 W3 #169 G family flex-b drain guard — the one call site where `.stack`
  // was already dead. `.notfound` declares `display: grid` later in the same
  // `@layer legacy` block, so it beat `.stack`'s `display: flex` on source
  // order. Migrating this site to `flex flex-col` like the other 37 would have
  // turned a centred grid into a flex column, and the route is outside the
  // smoke matrix so nothing would have caught it. The token is dropped and
  // deliberately NOT replaced by a utility: utilities beat `@layer legacy`
  // unconditionally, so adding `flex` here would break what `.stack` never won.
  it('drops the dead .stack without handing the layout to a utility', () => {
    const { container } = renderPage()

    const section = container.querySelector('.notfound')
    expect(section).not.toBeNull()
    expect(section).not.toHaveClass('stack')
    // the grid must keep coming from `.notfound`
    expect(section?.className).not.toMatch(/\bflex\b/)
    expect(section?.className).not.toMatch(/\bflex-col\b/)
    // the spacing utility from an earlier wave stays put
    expect(section).toHaveClass('gap-4')
  })
  /* eslint-enable testing-library/no-container, testing-library/no-node-access */
})
