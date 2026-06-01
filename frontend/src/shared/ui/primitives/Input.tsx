import { forwardRef } from 'react'

export interface InputProps {
  id: string
  label: string
  type?: 'text' | 'number' | 'date' | 'email' | 'password'
  name?: string
  value?: string | number
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
    value,
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
    <div className="field">
      <label htmlFor={id}>{label}</label>
      <input
        ref={ref}
        id={id}
        type={type}
        name={name}
        value={value}
        defaultValue={defaultValue}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        placeholder={placeholder}
        min={min}
        max={max}
        aria-invalid={error !== undefined}
        aria-describedby={errorId}
        className="input"
        style={error !== undefined ? { borderColor: 'var(--danger)' } : undefined}
      />
      {error !== undefined ? (
        <span id={errorId} className="t-tiny danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
