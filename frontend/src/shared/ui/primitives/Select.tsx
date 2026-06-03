import { forwardRef } from 'react'

export interface SelectOption {
  value: string
  label: string
}

export interface SelectProps {
  id: string
  label: string
  options: SelectOption[]
  name?: string
  value?: string
  defaultValue?: string
  onChange?: React.ChangeEventHandler<HTMLSelectElement>
  onBlur?: React.FocusEventHandler<HTMLSelectElement>
  disabled?: boolean
  error?: string | undefined
  /** Visually hide the label (still read by assistive tech). */
  labelHidden?: boolean
}

/**
 * Select — labelled dropdown. Compatible with React Hook Form's `register()`
 * (spread `name`, `onChange`, `onBlur`, `ref`) or controlled via `value`.
 */
export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  {
    id,
    label,
    options,
    name,
    value,
    defaultValue,
    onChange,
    onBlur,
    disabled = false,
    error,
    labelHidden = false,
  },
  ref,
) {
  const errorId = error !== undefined ? `${id}-error` : undefined

  return (
    <div className="field">
      <label htmlFor={id} className={labelHidden ? 'sr-only' : undefined}>
        {label}
      </label>
      <select
        ref={ref}
        id={id}
        name={name}
        value={value}
        defaultValue={defaultValue}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        aria-invalid={error !== undefined}
        aria-describedby={errorId}
        className="select"
        style={error !== undefined ? { borderColor: 'var(--danger)' } : undefined}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error !== undefined ? (
        <span id={errorId} className="t-tiny danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
