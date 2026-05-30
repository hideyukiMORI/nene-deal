import type { Meta, StoryObj } from '@storybook/react-vite'
import { Button } from '@/shared/ui/primitives/Button'
import { EmptyState } from './EmptyState'

/**
 * EmptyState — composed empty panel.
 *
 * In:  title, description, action
 * Out: (via action slot callbacks)
 *
 * Does not: fetch lists or navigate routes.
 */
const meta = {
  title: 'Components/EmptyState',
  component: EmptyState,
  args: {
    title: 'まだステージがありません',
    description: 'パイプラインのステージが設定されていません。',
  },
} satisfies Meta<typeof EmptyState>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {}
export const WithAction: Story = {
  args: { action: <Button>ディールを追加</Button> },
}
