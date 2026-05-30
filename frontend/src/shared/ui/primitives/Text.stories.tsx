import type { Meta, StoryObj } from '@storybook/react-vite'
import { Text } from './Text'

/**
 * Text — typographic primitive.
 *
 * In:  as (element), variant, muted, children
 * Out: (none — presentational)
 *
 * Does not: fetch data or own layout/spacing beyond the text node.
 */
const meta = {
  title: 'Primitives/Text',
  component: Text,
  args: { children: 'パイプライン / Sales pipeline' },
  argTypes: {
    variant: { control: 'select', options: ['body', 'caption', 'heading-sm', 'heading-md'] },
    as: { control: 'select', options: ['p', 'span', 'h1', 'h2', 'h3'] },
  },
} satisfies Meta<typeof Text>

export default meta
type Story = StoryObj<typeof meta>

export const Body: Story = { args: { variant: 'body' } }
export const Caption: Story = { args: { variant: 'caption', muted: true } }
export const HeadingSm: Story = { args: { variant: 'heading-sm', as: 'h2' } }
export const HeadingMd: Story = { args: { variant: 'heading-md', as: 'h1' } }
