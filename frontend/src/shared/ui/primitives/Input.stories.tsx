import type { Meta, StoryObj } from '@storybook/react-vite'
import { fn } from 'storybook/test'
import { Input } from './Input'

/**
 * Input — labelled text/number field.
 *
 * In:  id, label, type, name, defaultValue, disabled, error, placeholder, min, max
 * Out: onChange(event), onBlur(event)
 *
 * Does not: validate (forms own validation), fetch, or know entity ids.
 */
const meta = {
  title: 'Primitives/Input',
  component: Input,
  args: {
    id: 'account',
    label: '取引先名',
    onChange: fn(),
  },
} satisfies Meta<typeof Input>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}
export const WithError: Story = {
  args: { error: '取引先名を入力してください。' },
}
export const Number: Story = {
  args: { id: 'amount', label: '金額（円）', type: 'number' },
}
export const Disabled: Story = {
  args: { disabled: true },
}
