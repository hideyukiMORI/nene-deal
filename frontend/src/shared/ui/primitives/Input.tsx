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
  /** Native `<input min>`; HTML allows a number or a string (e.g. date bounds). */
  min?: number | string
  /** Native `<input max>`; HTML allows a number or a string (e.g. date bounds). */
  max?: number | string
  step?: number | string
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
    step,
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
        step={step}
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
