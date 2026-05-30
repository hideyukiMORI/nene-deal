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
    <div className="flex flex-col gap-stack-xs">
      <label
        htmlFor={id}
        className={labelHidden ? 'sr-only' : 'font-sans text-body font-medium text-text-primary'}
      >
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
        className={[
          'rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm',
          'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
          error !== undefined ? 'border-danger' : '',
        ]
          .filter(Boolean)
          .join(' ')}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
