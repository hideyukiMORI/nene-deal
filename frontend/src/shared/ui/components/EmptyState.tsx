import { Text } from '@/shared/ui/primitives/Text'

export interface EmptyStateProps {
  title: string
  description?: string
  action?: React.ReactNode
}

/**
 * EmptyState — placeholder when a list or panel has no items.
 *
 * In:  title, description, action (slot)
 * Out: (none — action slot emits via child components)
 *
 * Does not: know why data is empty or trigger fetches.
 */
export function EmptyState({ title, description, action }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center gap-stack-md rounded-md border border-border bg-surface-raised px-inline-lg py-stack-lg text-center shadow-sm">
      <Text as="h2" variant="heading-sm">
        {title}
      </Text>
      {description !== undefined ? <Text muted>{description}</Text> : null}
      {action !== undefined ? <div>{action}</div> : null}
    </div>
  )
}
