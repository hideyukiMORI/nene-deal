export type TextVariant = 'body' | 'caption' | 'heading-sm' | 'heading-md'
export type TextElement = 'p' | 'span' | 'h1' | 'h2' | 'h3'

export interface TextProps {
  as?: TextElement
  variant?: TextVariant
  muted?: boolean
  children: React.ReactNode
  className?: string
  id?: string
}

const variantClasses: Record<TextVariant, string> = {
  body: 'font-sans text-body leading-body font-normal',
  caption: 'font-sans text-caption leading-body font-normal',
  'heading-sm': 'font-display text-heading-sm leading-heading font-semibold tracking-tight',
  'heading-md': 'font-display text-heading-md leading-heading font-semibold tracking-tight',
}

export function Text({
  as: Component = 'p',
  variant = 'body',
  muted = false,
  children,
  className,
  id,
}: TextProps) {
  const classes = [
    variantClasses[variant],
    muted ? 'text-text-muted' : 'text-text-primary',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <Component id={id} className={classes}>
      {children}
    </Component>
  )
}
