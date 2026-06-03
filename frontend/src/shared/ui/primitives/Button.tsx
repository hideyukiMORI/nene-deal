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
  primary: 'btn-primary',
  secondary: 'btn-secondary',
  danger: 'btn-danger',
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
  const classes = ['btn', variantClasses[variant], size === 'sm' ? 'btn-sm' : '', className]
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
