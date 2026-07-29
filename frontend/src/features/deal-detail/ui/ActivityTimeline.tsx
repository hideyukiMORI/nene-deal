import { useState, type ReactNode } from 'react'
import type { DealActivity, DealActivityAction } from '@/entities/deal'
import type { PipelineStage } from '@/entities/pipeline-stage'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import {
  IconArrowRight,
  IconInvoice,
  IconMore,
  IconPencil,
  IconPlus,
  IconRestore,
  IconTrash,
} from '@/shared/ui/icons'

type Tone = '' | 'ok' | 'muted' | 'danger'

const ACTION: Record<DealActivityAction, { tone: Tone; label: MessageKey; Icon: typeof IconPlus }> =
  {
    created: { tone: 'muted', label: 'detail.activity.created', Icon: IconPlus },
    updated: { tone: '', label: 'detail.activity.updated', Icon: IconPencil },
    stage_changed: { tone: 'ok', label: 'detail.activity.stageChanged', Icon: IconArrowRight },
    deleted: { tone: 'danger', label: 'detail.activity.deleted', Icon: IconTrash },
    restored: { tone: 'ok', label: 'detail.activity.restored', Icon: IconRestore },
    handoff: { tone: '', label: 'detail.activity.handoff', Icon: IconInvoice },
  }

const FIELD_LABEL: Record<string, MessageKey> = {
  account_label: 'deal.field.accountLabel',
  amount_cents: 'deal.field.amount',
  probability_percent: 'deal.field.probability',
  expected_close_date: 'deal.field.expectedCloseDate',
  owner_user_id: 'deal.field.owner',
  note: 'deal.field.note',
}

export interface ActivityTimelineProps {
  activity: DealActivity[]
  stages: PipelineStage[]
  locale: string
}

function fmtValue(field: string, value: unknown, locale: string): string {
  if (value === null || value === undefined || value === '') return '—'
  if (field === 'amount_cents' && typeof value === 'number') return formatMoneyJpy(value, locale)
  if (typeof value === 'string') return value
  if (typeof value === 'number' || typeof value === 'boolean') return String(value)
  return JSON.stringify(value)
}

function fmtTime(raw: string, locale: string): string {
  const date = new Date(raw.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return raw
  return new Intl.DateTimeFormat(locale === 'ja' ? 'ja-JP' : 'en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

const HEAD = 3
const TAIL = 2

export function ActivityTimeline({ activity, stages, locale }: ActivityTimelineProps) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)

  const stageLabel = (id: string | null): string =>
    id === null ? '—' : (stages.find((s) => s.id === id)?.label ?? id)

  function changeNode(entry: DealActivity): ReactNode {
    if (entry.action === 'stage_changed') {
      return (
        <span className="tl-change mono">
          <span className="text-text-faint line-through">{stageLabel(entry.fromStageId)}</span>
          <IconArrowRight />
          <span className="text-text-primary">{stageLabel(entry.toStageId)}</span>
        </span>
      )
    }
    if (entry.changes.length === 0) return null
    return entry.changes.map((c) => {
      const fieldLabel = FIELD_LABEL[c.field]
      return (
        <span key={c.field} className="tl-change mono">
          {fieldLabel ? `${t(fieldLabel)} ` : `${c.field} `}
          <span className="text-text-faint line-through">{fmtValue(c.field, c.from, locale)}</span>
          <IconArrowRight />
          <span className="text-text-primary">{fmtValue(c.field, c.to, locale)}</span>
        </span>
      )
    })
  }

  function item(entry: DealActivity): ReactNode {
    const meta = ACTION[entry.action]
    const Icon = meta.Icon
    return (
      <li key={entry.id} className="tl-item">
        <span className="tl-rail">
          <span className={`tl-node ${meta.tone}`}>
            <Icon />
          </span>
        </span>
        <div className="flex min-w-0 flex-col gap-1 pt-1">
          <span className="font-semibold text-body">{t(meta.label)}</span>
          {changeNode(entry)}
          <span className="text-text-faint font-mono text-caption">
            {(entry.actorLabel ?? t('detail.owner.empty')) +
              ' · ' +
              fmtTime(entry.createdAt, locale)}
          </span>
        </div>
      </li>
    )
  }

  if (activity.length === 0) {
    return (
      <section aria-label={t('detail.activity.title')} className="card card-pad stack g4">
        <div className="panel-title" style={{ marginBottom: 0 }}>
          <h2 className="t-h2">{t('detail.activity.title')}</h2>
        </div>
        <span className="muted t-cap" style={{ marginTop: -8 }}>
          {t('detail.activity.empty')}
        </span>
      </section>
    )
  }

  const collapsed = !open && activity.length > HEAD + TAIL
  const hidden = activity.length - HEAD - TAIL

  return (
    <section aria-label={t('detail.activity.title')} className="card card-pad stack g4">
      <div className="panel-title" style={{ marginBottom: 0 }}>
        <h2 className="t-h2">{t('detail.activity.title')}</h2>
      </div>
      <span className="muted t-cap" style={{ marginTop: -8 }}>
        {t('detail.activity.sub')}
      </span>

      <ul className="m-0 flex list-none flex-col p-0 pt-0.5">
        {collapsed ? (
          <>
            {activity.slice(0, HEAD).map(item)}
            <li className="tl-item tl-gap">
              <span className="tl-rail">
                <button
                  type="button"
                  className="tl-gapnode"
                  aria-label={t('detail.activity.expand', { count: hidden })}
                  onClick={() => {
                    setOpen(true)
                  }}
                >
                  <IconMore />
                </button>
              </span>
              <div className="flex min-w-0 flex-col gap-1 pt-1">
                <button
                  type="button"
                  className="tl-gapbtn"
                  onClick={() => {
                    setOpen(true)
                  }}
                >
                  {t('detail.activity.expand', { count: hidden })}
                </button>
              </div>
            </li>
            {activity.slice(activity.length - TAIL).map(item)}
          </>
        ) : (
          activity.map(item)
        )}
      </ul>
    </section>
  )
}
