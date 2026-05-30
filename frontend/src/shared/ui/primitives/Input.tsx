import { forwardRef } from 'react'

export interface InputProps {
  id: string
  label: string
  type?: 'text' | 'number' | 'date' | 'email' | 'password'
  name?: string
  defaultValue?: string | number
  onChange?: React.ChangeEventHandler<HTMLInputElement>
  onBlur?: React.FocusEventHandler<HTMLInputElement>
  disabled?: boolean
  error?: string | undefined
  placeholder?: string
  min?: number
  max?: number
}

/**
 * Input — labelled text/number field. Compatible with React Hook Form's
 * `register()` (spread `name`, `onChange`, `onBlur`, `ref`); `error` links the
 * message via `aria-describedby`.
 */
export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  {
    id,
    label,
    type = 'text',
    name,
    defaultValue,
    onChange,
    onBlur,
    disabled = false,
    error,
    placeholder,
    min,
    max,
  },
  ref,
) {
  const errorId = error !== undefined ? `${id}-error` : undefined

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>
      <input
        ref={ref}
        id={id}
        type={type}
        name={name}
        defaultValue={defaultValue}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        placeholder={placeholder}
        min={min}
        max={max}
        aria-invalid={error !== undefined}
        aria-describedby={errorId}
        className={[
          'rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm',
          'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
          error !== undefined ? 'border-danger' : '',
        ]
          .filter(Boolean)
          .join(' ')}
      />
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
