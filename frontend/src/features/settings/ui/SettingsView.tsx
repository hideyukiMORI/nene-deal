import { Badge, Button, FormField, Select } from '@hideyukimori/nene2-ui'
import type { SelectOption } from '@/shared/ui/select-option'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui/toast/use-toast'
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
    <section className="content content-narrow flex flex-col">
      <div className="row justify-between flex-wrap g4 page-head">
        <div className="flex flex-col gap-1">
          <h1 className="t-h1">{t('settings.title')}</h1>
          <span className="muted t-cap">{t('settings.subtitle')}</span>
        </div>
        <Badge tone="accent">
          <IconShield />
          {t('audit.adminOnly')}
        </Badge>
      </div>

      <form
        className="card card-pad flex flex-col gap-5"
        onSubmit={(event) => {
          event.preventDefault()
        }}
      >
        <div className="panel-title">
          <h2 className="t-h2">{t('settings.forecast.title')}</h2>
        </div>

        <div style={{ maxWidth: 280 }}>
          <FormField id="forecast-closing-day" label={t('settings.closingDay.label')}>
            <Select
              value={value}
              disabled={loading}
              onChange={(event) => {
                setDraft(event.target.value)
              }}
            >
              {dayOptions(t('settings.closingDay.monthEnd')).map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <span className="faint t-tiny" style={{ maxWidth: '52ch' }}>
          {t('settings.closingDay.hint')}
        </span>

        <div className="flex items-center gap-3">
          <Button type="button" disabled={saving || loading} onClick={onSave}>
            {t('common.actions.save')}
          </Button>
        </div>
      </form>
    </section>
  )
}
