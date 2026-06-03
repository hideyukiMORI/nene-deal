import { useState } from 'react'
import { useSettings, useUpdateSettings } from '@/entities/settings'
import { useTranslation } from '@/shared/i18n'
import { Button, Select, useToast, type SelectOption } from '@/shared/ui'
import { IconShield } from '@/shared/ui/icons'

const MONTH_END = 'month-end'

function dayOptions(monthEndLabel: string): SelectOption[] {
  const days: SelectOption[] = [{ value: MONTH_END, label: monthEndLabel }]
  for (let d = 1; d <= 28; d += 1) {
    days.push({ value: String(d), label: String(d) })
  }
  return days
}

export function SettingsView() {
  const { t } = useTranslation()
  const toast = useToast()
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

  const onSave = (): void => {
    const closingDay = value === MONTH_END ? null : Number(value)
    void update.mutateAsync(closingDay).then(
      () => {
        toast.success(t('toast.saved.title'), t('toast.saved.sub'))
      },
      () => {
        toast.error(t('settings.error'))
      },
    )
  }

  return (
    <section className="content content-narrow stack g6">
      <div className="row between wrap g4 page-head">
        <div className="stack g1">
          <h1 className="t-h1">{t('settings.title')}</h1>
          <span className="muted t-cap">{t('settings.subtitle')}</span>
        </div>
        <span className="badge badge-accent row g1">
          <IconShield />
          {t('audit.adminOnly')}
        </span>
      </div>

      <form
        className="card card-pad stack g5"
        onSubmit={(event) => {
          event.preventDefault()
        }}
      >
        <div className="panel-title">
          <h2 className="t-h2">{t('settings.forecast.title')}</h2>
        </div>

        <div style={{ maxWidth: 280 }}>
          <Select
            id="forecast-closing-day"
            label={t('settings.closingDay.label')}
            options={dayOptions(t('settings.closingDay.monthEnd'))}
            value={value}
            disabled={settings.isPending}
            onChange={(event) => {
              setDraft(event.target.value)
            }}
          />
        </div>

        <span className="faint t-tiny" style={{ maxWidth: '52ch' }}>
          {t('settings.closingDay.hint')}
        </span>

        <div className="row g3">
          <Button type="button" disabled={update.isPending || settings.isPending} onClick={onSave}>
            {t('common.actions.save')}
          </Button>
        </div>
      </form>
    </section>
  )
}
