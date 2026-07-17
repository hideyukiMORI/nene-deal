import { useState } from 'react'
import { useSettings, useUpdateSettings } from '@/entities/settings'

/** Sentinel option value for "calendar month-end" (persisted as `null`). */
export const MONTH_END = 'month-end'

export interface SettingsPage {
  /** The selected closing-day option (draft layered over the loaded value). */
  value: string
  setDraft: (value: string) => void
  loading: boolean
  saving: boolean
  /** Persists the current selection; resolves `true` on success. */
  save: () => Promise<boolean>
}

export function useSettingsPage(): SettingsPage {
  const settings = useSettings()
  const update = useUpdateSettings()

  // The selected value is a local draft layered over the loaded setting, so we
  // avoid syncing state in an effect.
  const [draft, setDraft] = useState<string | null>(null)
  const loaded =
    settings.data && settings.data.forecastClosingDay !== null
      ? String(settings.data.forecastClosingDay)
      : MONTH_END
  const value = draft ?? loaded

  const save = async (): Promise<boolean> => {
    const closingDay = value === MONTH_END ? null : Number(value)
    try {
      await update.mutateAsync(closingDay)
      return true
    } catch {
      return false
    }
  }

  return {
    value,
    setDraft,
    loading: settings.isPending,
    saving: update.isPending,
    save,
  }
}
