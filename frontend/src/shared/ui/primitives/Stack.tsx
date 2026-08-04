export type StackDirection = 'vertical' | 'horizontal'
export type StackGap = 'xs' | 'sm' | 'md' | 'lg'

export interface StackProps {
  direction?: StackDirection
  gap?: StackGap
  children: React.ReactNode
  className?: string
}

const gapClasses: Record<StackGap, string> = {
  xs: 'gap-1',
  sm: 'gap-2',
  md: 'gap-4',
  lg: 'gap-6',
}

export function Stack({ direction = 'vertical', gap = 'md', children, className }: StackProps) {
  const classes = [
    direction === 'vertical' ? 'flex flex-col' : 'flex items-center',
    gapClasses[gap],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return <div className={classes}>{children}</div>
}
