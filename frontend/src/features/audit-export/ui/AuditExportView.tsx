import { type ReactNode } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Button } from '@/shared/ui/primitives/Button'
import { Input } from '@/shared/ui/primitives/Input'
import { useToast } from '@/shared/ui/toast/use-toast'
import {
  IconArrowRight,
  IconInvoice,
  IconPencil,
  IconPlus,
  IconRestore,
  IconShield,
  IconTrash,
} from '@/shared/ui/icons'
import type { AuditExportPage } from '../model/use-audit-export-page'

const RECORDED: { Icon: typeof IconPlus; label: MessageKey }[] = [
  { Icon: IconPlus, label: 'audit.chip.created' },
  { Icon: IconPencil, label: 'audit.chip.edited' },
  { Icon: IconArrowRight, label: 'audit.chip.moved' },
  { Icon: IconTrash, label: 'audit.chip.deleted' },
  { Icon: IconRestore, label: 'audit.chip.restored' },
  { Icon: IconInvoice, label: 'audit.chip.handoff' },
]

export type AuditExportViewProps = AuditExportPage

export function AuditExportView({
  from,
  to,
  setFrom,
  setTo,
  invalidRange,
  pending,
  download,
}: AuditExportViewProps) {
  const { t } = useTranslation()
  const toast = useToast()

  const onDownload = (): void => {
    if (invalidRange || pending) return
    void download().then((ok) => {
      if (ok) {
        toast.success(t('toast.audit.success.title'), `${from} – ${to}`)
      } else {
        toast.error(t('toast.audit.error.title'))
      }
    })
  }

  return (
    <section className="content content-narrow stack">
      <div className="row between wrap g4 page-head">
        <div className="stack gap-1">
          <h1 className="t-h1">{t('audit.title')}</h1>
          <span className="muted t-cap">{t('audit.subtitle')}</span>
        </div>
        <span className="badge badge-accent row">
          <IconShield />
          {t('audit.adminOnly')}
        </span>
      </div>

      <form
        className="card card-pad stack gap-5"
        onSubmit={(event) => {
          event.preventDefault()
        }}
      >
        <div className="panel-title">
          <h2 className="t-h2">{t('audit.rangeTitle')}</h2>
        </div>

        <div className="row gap-4 wrap">
          <div className="grow">
            <Input
              id="audit-from"
              label={t('audit.from')}
              type="date"
              value={from}
              onChange={(event) => {
                setFrom(event.target.value)
              }}
            />
          </div>
          <div className="grow">
            <Input
              id="audit-to"
              label={t('audit.to')}
              type="date"
              value={to}
              onChange={(event) => {
                setTo(event.target.value)
              }}
            />
          </div>
        </div>

        <div className="stack gap-2">
          <span className="eyebrow">{t('audit.recorded')}</span>
          <div className="row wrap gap-2">
            {RECORDED.map(
              ({ Icon, label }): ReactNode => (
                <span key={label} className="audit-chip">
                  <Icon />
                  {t(label)}
                </span>
              ),
            )}
          </div>
        </div>

        {invalidRange ? <span className="t-cap danger">{t('audit.invalidRange')}</span> : null}

        <div className="row gap-4 wrap" style={{ alignItems: 'center' }}>
          <Button type="button" disabled={pending || invalidRange} onClick={onDownload}>
            {pending ? t('audit.downloading') : t('audit.download')}
          </Button>
          <span className="faint t-tiny" style={{ maxWidth: '40ch' }}>
            {t('audit.hint')}
          </span>
        </div>
      </form>

      <div className="card card-pad row between wrap audit-cols">
        <span className="eyebrow">{t('audit.colsLabel')}</span>
        <code className="mono t-cap">{t('audit.colsVal')}</code>
      </div>
    </section>
  )
}
