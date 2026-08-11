import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Text } from './Text'

/**
 * C5 W3 #169 G family typography drain guard.
 *
 * `Text` maps its variants to class names through an object literal, which is
 * the third of the FC-1 five call forms — invisible to a `className="…"` grep
 * and, more importantly, invisible to the route matrix: every `<Text>` in this
 * app sits inside a form, an empty state, or the root error boundary, so the
 * computed probe that proved this wave never rendered one.
 *
 * `body` moved to the `text-body` utility (`--text-body: 0.875rem` = 14px,
 * identical to what `body { font-size: 14px }` already supplied at every call
 * site). The other three variants deliberately did NOT move: their legacy
 * values do not match the `@theme` scale (`.t-cap` 12.5px vs `--text-caption`
 * 12px, `.t-h2` carries a line-height and letter-spacing with no token, `.t-h1`
 * is 30px vs `--text-heading-md` 28px and additionally flips at the 1024px
 * boundary). Migrating them would change the rendering, so they wait on a token
 * ruling. These assertions pin that split: if someone moves one of the three
 * without the ruling, this test fails rather than the design changing quietly.
 */
describe('Text — typography drain guard', () => {
  it('renders the body variant with the token utility, not the drained .t-body', () => {
    render(<Text>body copy</Text>)

    const el = screen.getByText('body copy')
    expect(el).toHaveClass('text-body')
    expect(el).not.toHaveClass('t-body')
  })

  it('keeps the three unmigrated variants on their legacy classes', () => {
    render(
      <>
        <Text variant="caption">caption copy</Text>
        <Text variant="heading-sm">small heading</Text>
        <Text variant="heading-md">large heading</Text>
      </>,
    )

    expect(screen.getByText('caption copy')).toHaveClass('t-cap')
    expect(screen.getByText('small heading')).toHaveClass('t-h2')
    expect(screen.getByText('large heading')).toHaveClass('t-h1')
  })

  it('still composes muted and caller-supplied classes', () => {
    render(
      <Text muted className="danger">
        muted copy
      </Text>,
    )

    const el = screen.getByText('muted copy')
    expect(el).toHaveClass('text-body')
    expect(el).toHaveClass('muted')
    expect(el).toHaveClass('danger')
  })
})
