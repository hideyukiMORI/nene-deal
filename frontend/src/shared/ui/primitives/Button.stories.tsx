import type { Meta, StoryObj } from '@storybook/react-vite'
import { fn } from 'storybook/test'
import { Button } from './Button'

/**
 * Button — primary action control.
 *
 * In:  variant, size, disabled, type, children (label)
 * Out: onClick(event), onFocus(event), onBlur(event)
 *
 * Does not: fetch data, know entity ids, or read router/query cache.
 */
const meta = {
  title: 'Primitives/Button',
  component: Button,
  args: {
    children: 'Create',
    onClick: fn(),
  },
  argTypes: {
    variant: { control: 'select', options: ['primary', 'secondary', 'danger'] },
    size: { control: 'select', options: ['sm', 'md'] },
  },
} satisfies Meta<typeof Button>

export default meta
type Story = StoryObj<typeof meta>

export const Primary: Story = { args: { variant: 'primary' } }
export const Secondary: Story = { args: { variant: 'secondary' } }
export const Danger: Story = { args: { variant: 'danger' } }
export const Disabled: Story = { args: { disabled: true } }
export const Small: Story = { args: { size: 'sm' } }
