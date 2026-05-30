export type ButtonVariant = 'primary' | 'secondary' | 'danger'
export type ButtonSize = 'sm' | 'md'

export interface ButtonProps {
  variant?: ButtonVariant
  size?: ButtonSize
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  children: React.ReactNode
  className?: string
  onClick?: (event: React.MouseEvent<HTMLButtonElement>) => void
  onFocus?: (event: React.FocusEvent<HTMLButtonElement>) => void
  onBlur?: (event: React.FocusEvent<HTMLButtonElement>) => void
}

const variantClasses: Record<ButtonVariant, string> = {
  primary:
    'bg-accent text-text-inverse hover:bg-accent-hover border-transparent focus-visible:shadow-focus',
  secondary:
    'bg-transparent text-text-primary hover:bg-surface-overlay border-border focus-visible:shadow-focus',
  danger:
    'bg-transparent text-danger hover:bg-danger hover:text-text-inverse border-danger focus-visible:shadow-focus',
}

const sizeClasses: Record<ButtonSize, string> = {
  sm: 'px-inline-sm py-stack-xs text-caption',
  md: 'px-inline-md py-stack-sm text-body',
}

export function Button({
  variant = 'primary',
  size = 'md',
  disabled = false,
  type = 'button',
  children,
  className,
  onClick,
  onFocus,
  onBlur,
}: ButtonProps) {
  const classes = [
    'inline-flex items-center justify-center rounded-sm border font-sans font-semibold tracking-tight transition-colors duration-fast ease-default',
    'focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
    variantClasses[variant],
    sizeClasses[size],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <button
      type={type}
      disabled={disabled}
      className={classes}
      onClick={onClick}
      onFocus={onFocus}
      onBlur={onBlur}
    >
      {children}
    </button>
  )
}
