import { useState, type ReactNode } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Button, Input, useToast } from '@/shared/ui'
import {
  IconArrowRight,
  IconInvoice,
  IconPencil,
  IconPlus,
  IconRestore,
  IconShield,
  IconTrash,
} from '@/shared/ui/icons'
import { downloadAuditCsv } from '../download-audit-csv'

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

function firstOfMonth(): string {
  return `${today().slice(0, 7)}-01`
}

const RECORDED: { Icon: typeof IconPlus; label: MessageKey }[] = [
  { Icon: IconPlus, label: 'audit.chip.created' },
  { Icon: IconPencil, label: 'audit.chip.edited' },
  { Icon: IconArrowRight, label: 'audit.chip.moved' },
  { Icon: IconTrash, label: 'audit.chip.deleted' },
  { Icon: IconRestore, label: 'audit.chip.restored' },
  { Icon: IconInvoice, label: 'audit.chip.handoff' },
]

export function AuditExportView() {
  const { t } = useTranslation()
  const toast = useToast()
  const [from, setFrom] = useState(firstOfMonth)
  const [to, setTo] = useState(today)
  const [pending, setPending] = useState(false)

  const invalidRange = from > to

  const onDownload = (): void => {
    if (invalidRange || pending) return
    setPending(true)
    void (async () => {
      try {
        await downloadAuditCsv(from, to)
        toast.success(t('toast.audit.success.title'), `${from} – ${to}`)
      } catch {
        toast.error(t('toast.audit.error.title'))
      } finally {
        setPending(false)
      }
    })()
  }

  return (
    <section className="content content-narrow stack g6">
      <div className="row between wrap g4 page-head">
        <div className="stack g1">
          <h1 className="t-h1">{t('audit.title')}</h1>
          <span className="muted t-cap">{t('audit.subtitle')}</span>
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
          <h2 className="t-h2">{t('audit.rangeTitle')}</h2>
        </div>

        <div className="row g4 wrap">
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

        <div className="stack g2">
          <span className="eyebrow">{t('audit.recorded')}</span>
          <div className="row wrap g2">
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

        <div className="row g4 wrap" style={{ alignItems: 'center' }}>
          <Button type="button" disabled={pending || invalidRange} onClick={onDownload}>
            {pending ? t('audit.downloading') : t('audit.download')}
          </Button>
          <span className="faint t-tiny" style={{ maxWidth: '40ch' }}>
            {t('audit.hint')}
          </span>
        </div>
      </form>

      <div className="card card-pad row between wrap g3 audit-cols">
        <span className="eyebrow">{t('audit.colsLabel')}</span>
        <code className="mono t-cap">{t('audit.colsVal')}</code>
      </div>
    </section>
  )
}
