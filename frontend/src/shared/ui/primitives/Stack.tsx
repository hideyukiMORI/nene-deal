export type StackDirection = 'vertical' | 'horizontal'
export type StackGap = 'xs' | 'sm' | 'md' | 'lg'

export interface StackProps {
  direction?: StackDirection
  gap?: StackGap
  children: React.ReactNode
  className?: string
}

const gapClasses: Record<StackGap, string> = {
  xs: 'gap-stack-xs',
  sm: 'gap-stack-sm',
  md: 'gap-stack-md',
  lg: 'gap-stack-lg',
}

export function Stack({ direction = 'vertical', gap = 'md', children, className }: StackProps) {
  const classes = [
    'flex',
    direction === 'vertical' ? 'flex-col' : 'flex-row items-center',
    gapClasses[gap],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return <div className={classes}>{children}</div>
}
