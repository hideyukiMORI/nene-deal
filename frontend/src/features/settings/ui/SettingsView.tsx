import { useTranslation } from '@/shared/i18n'
import { Button, Select, useToast, type SelectOption } from '@/shared/ui'
import { IconShield } from '@/shared/ui/icons'
import { MONTH_END, type SettingsPage } from '../model/use-settings-page'

function dayOptions(monthEndLabel: string): SelectOption[] {
  const days: SelectOption[] = [{ value: MONTH_END, label: monthEndLabel }]
  for (let d = 1; d <= 28; d += 1) {
    days.push({ value: String(d), label: String(d) })
  }
  return days
}

export type SettingsViewProps = SettingsPage

export function SettingsView({ value, setDraft, loading, saving, save }: SettingsViewProps) {
  const { t } = useTranslation()
  const toast = useToast()

  const onSave = (): void => {
    void save().then((ok) => {
      if (ok) {
        toast.success(t('toast.saved.title'), t('toast.saved.sub'))
      } else {
        toast.error(t('settings.error'))
      }
    })
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
            disabled={loading}
            onChange={(event) => {
              setDraft(event.target.value)
            }}
          />
        </div>

        <span className="faint t-tiny" style={{ maxWidth: '52ch' }}>
          {t('settings.closingDay.hint')}
        </span>

        <div className="row g3">
          <Button type="button" disabled={saving || loading} onClick={onSave}>
            {t('common.actions.save')}
          </Button>
        </div>
      </form>
    </section>
  )
}
