import type { Meta, StoryObj } from '@storybook/react-vite'
import { Button } from './Button'
import { Stack } from './Stack'
import { Text } from './Text'

/**
 * Stack — layout primitive for spacing children.
 *
 * In:  direction, gap, children
 * Out: (none — presentational)
 *
 * Does not: manage scroll, grid columns, or responsive breakpoints beyond flex.
 */
const meta = {
  title: 'Primitives/Stack',
  component: Stack,
  args: {
    children: (
      <>
        <Text variant="heading-sm">パイプライン</Text>
        <Text muted>今月の着地見込み</Text>
        <Button>ディールを追加</Button>
      </>
    ),
  },
} satisfies Meta<typeof Stack>

export default meta
type Story = StoryObj<typeof meta>

export const Vertical: Story = {}
export const Horizontal: Story = {
  args: {
    direction: 'horizontal',
    gap: 'sm',
    children: (
      <>
        <Button variant="secondary">キャンセル</Button>
        <Button>作成</Button>
      </>
    ),
  },
}
