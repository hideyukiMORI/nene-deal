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
  body: 't-body',
  caption: 't-cap',
  'heading-sm': 't-h2',
  'heading-md': 't-h1',
}

export function Text({
  as: Component = 'p',
  variant = 'body',
  muted = false,
  children,
  className,
  id,
}: TextProps) {
  const classes = [variantClasses[variant], muted ? 'muted' : '', className]
    .filter(Boolean)
    .join(' ')

  return (
    <Component id={id} className={classes}>
      {children}
    </Component>
  )
}
