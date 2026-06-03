export type StackDirection = 'vertical' | 'horizontal'
export type StackGap = 'xs' | 'sm' | 'md' | 'lg'

export interface StackProps {
  direction?: StackDirection
  gap?: StackGap
  children: React.ReactNode
  className?: string
}

const gapClasses: Record<StackGap, string> = {
  xs: 'g1',
  sm: 'g2',
  md: 'g4',
  lg: 'g6',
}

export function Stack({ direction = 'vertical', gap = 'md', children, className }: StackProps) {
  const classes = [direction === 'vertical' ? 'stack' : 'row', gapClasses[gap], className]
    .filter(Boolean)
    .join(' ')

  return <div className={classes}>{children}</div>
}
