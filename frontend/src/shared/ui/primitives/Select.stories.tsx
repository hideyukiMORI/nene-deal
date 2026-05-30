import type { Meta, StoryObj } from '@storybook/react-vite'
import { fn } from 'storybook/test'
import { Select } from './Select'

/**
 * Select — labelled dropdown.
 *
 * In:  id, label, options, name, value, defaultValue, disabled, error, labelHidden
 * Out: onChange(event), onBlur(event)
 *
 * Does not: fetch options, validate, or know entity ids.
 */
const meta = {
  title: 'Primitives/Select',
  component: Select,
  args: {
    id: 'stage',
    label: 'ステージ',
    onChange: fn(),
    options: [
      { value: 'lead', label: 'Lead' },
      { value: 'proposal', label: 'Proposal' },
      { value: 'won', label: 'Won' },
    ],
  },
} satisfies Meta<typeof Select>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}
export const WithError: Story = { args: { error: 'ステージを選択してください。' } }
export const LabelHidden: Story = { args: { labelHidden: true } }
