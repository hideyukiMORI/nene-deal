import { useState } from 'react'
import type { KanbanColumn } from '@/entities/board'
import type { CreateDealInput } from '@/entities/deal'
import type { ForecastSummary } from '@/entities/forecast'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { EmptyState, type SelectOption } from '@/shared/ui'
import { IconChevron, IconPlus } from '@/shared/ui/icons'
import type { BoardStatus } from '../hooks/use-kanban-board-page'
import { CreateDealForm } from './CreateDealForm'

export interface KanbanBoardViewProps {
  status: BoardStatus
  errorMessageKey: MessageKey | null
  columns: KanbanColumn[]
  forecast: ForecastSummary | null
  stageOptions: SelectOption[]
  retry: () => void
  submitCreateDeal: (input: CreateDealInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  moveDeal: (dealId: string, toStageRef: string) => Promise<void>
  onOpenDeal: (dealId: string) => void
  onOpenUsers?: () => void
  onOpenStages?: () => void
  isAdmin?: boolean
}

const STAGE_COLORS: Record<string, string> = {
  lead: 'var(--info)',
  qualified: 'var(--warn)',
  proposal: 'var(--accent)',
  won: 'var(--ok)',
  lost: 'var(--fg-faint)',
}

function stageColor(slug: string): string {
  return STAGE_COLORS[slug] ?? 'var(--fg-faint)'
}

/** Format a `YYYY-MM` forecast month for the board subtitle. */
function monthLabel(month: string, locale: string): string {
  const [year, mon] = month.split('-').map(Number)
  if (year === undefined || mon === undefined || Number.isNaN(year) || Number.isNaN(mon)) {
    return month
  }
  return new Intl.DateTimeFormat(locale === 'ja' ? 'ja-JP' : 'en-US', {
    year: 'numeric',
    month: 'long',
  }).format(new Date(year, mon - 1, 1))
}

function ProbBar({ value }: { value: number }) {
  return (
    <span className="prob">
      <span className="prob-track">
        <span className="prob-fill" style={{ width: `${String(value)}%` }} />
      </span>
      {value}%
    </span>
  )
}

export function KanbanBoardView({
  status,
  errorMessageKey,
  columns,
  forecast,
  stageOptions,
  retry,
  submitCreateDeal,
  createPending,
  createErrorKey,
  onOpenDeal,
}: KanbanBoardViewProps) {
  const { t, locale } = useTranslation()
  const [formOpen, setFormOpen] = useState(false)

  return (
    <section className="content stack g6">
      <div className="row between wrap g4 page-head">
        <div className="stack g1">
          <h1 className="t-h1">{t('board.heading')}</h1>
          <span className="muted t-cap">
            {forecast !== null
              ? t('board.subtitle', {
                  month: monthLabel(forecast.month, locale),
                  count: forecast.openDealCount,
                })
              : t('app.subtitle')}
          </span>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          onClick={() => {
            setFormOpen((open) => !open)
          }}
        >
          <IconPlus />
          {t('deal.create.open')}
        </button>
      </div>

      {forecast !== null ? (
        <section aria-label={t('forecast.title')} className="forecast">
          <Stat label={t('forecast.openDealCount')} value={String(forecast.openDealCount)} />
          <Stat
            label={t('forecast.pipelineTotal')}
            value={formatMoneyJpy(forecast.pipelineTotalCents, locale)}
          />
          <Stat
            label={t('forecast.weightedTotal')}
            value={formatMoneyJpy(forecast.weightedTotalCents, locale)}
            accent
          />
          <Stat
            label={t('forecast.wonThisMonth')}
            value={formatMoneyJpy(
              forecast.byStage.find((bucket) => bucket.slug === 'won')?.totalCents ?? 0,
              locale,
            )}
          />
        </section>
      ) : null}

      {formOpen ? (
        <CreateDealForm
          stageOptions={stageOptions}
          pending={createPending}
          errorMessage={createErrorKey !== null ? t(createErrorKey) : null}
          onSubmit={async (input) => {
            const created = await submitCreateDeal(input)
            if (created) setFormOpen(false)
            return created
          }}
          onCancel={() => {
            setFormOpen(false)
          }}
        />
      ) : null}

      {status === 'loading' ? <p className="muted t-body">{t('board.loading')}</p> : null}

      {status === 'error' ? (
        <div className="stack g3">
          <h2 className="t-h2">{t('board.error.title')}</h2>
          <p className="muted t-body">
            {errorMessageKey !== null ? t(errorMessageKey) : t('common.error.unknown')}
          </p>
          <div className="row g3">
            <button type="button" className="btn btn-secondary" onClick={retry}>
              {t('common.actions.retry')}
            </button>
          </div>
        </div>
      ) : null}

      {status === 'ready' && columns.length === 0 ? (
        <EmptyState title={t('board.empty.title')} description={t('board.empty.description')} />
      ) : null}

      {status === 'ready' && columns.length > 0 ? (
        <div className="board">
          {columns.map((column) => (
            <BoardColumn
              key={column.stageId}
              column={column}
              moneyLocale={locale}
              summaryLabel={t('board.column.summary', {
                count: column.dealCount,
                weighted: formatMoneyJpy(column.weightedTotalCents, locale),
              })}
              emptyLabel={t('board.column.empty')}
              detailLabel={t('deal.open.detail')}
              wonBadgeLabel={t('stages.badge.won')}
              onOpenDeal={onOpenDeal}
            />
          ))}
        </div>
      ) : null}
    </section>
  )
}

function Stat({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
  return (
    <div className="stat">
      <span className="label">{label}</span>
      <span className="val" style={accent === true ? { color: 'var(--accent)' } : undefined}>
        {value}
      </span>
    </div>
  )
}

interface BoardColumnProps {
  column: KanbanColumn
  moneyLocale: string
  summaryLabel: string
  emptyLabel: string
  detailLabel: string
  wonBadgeLabel: string
  onOpenDeal: (dealId: string) => void
}

function BoardColumn({
  column,
  moneyLocale,
  summaryLabel,
  emptyLabel,
  detailLabel,
  wonBadgeLabel,
  onOpenDeal,
}: BoardColumnProps) {
  const isWon = column.stageSlug === 'won'
  return (
    <section className="col" aria-label={column.stageLabel}>
      <div className="col-head">
        <h2 className="col-name">
          <span className="stage-dot" style={{ background: stageColor(column.stageSlug) }} />
          {column.stageLabel}
        </h2>
        <span className="count">{column.dealCount}</span>
      </div>
      <span className="col-meta">{summaryLabel}</span>

      {column.deals.length === 0 ? (
        <span className="col-meta">{emptyLabel}</span>
      ) : (
        column.deals.map((deal) => (
          <article key={deal.id} className="deal">
            <div className="row between g2">
              <span className="acct">{deal.accountLabel}</span>
              {isWon ? (
                <span className="badge badge-ok">
                  <span className="dot" />
                  {wonBadgeLabel}
                </span>
              ) : null}
            </div>
            <span className="amt">{formatMoneyJpy(deal.amountCents, moneyLocale)}</span>
            <div className="deal-foot">
              <ProbBar value={deal.probabilityPercent} />
              <button
                type="button"
                className="details-link row g1 faint t-tiny"
                style={{ border: 'none', background: 'none', cursor: 'pointer', font: 'inherit' }}
                onClick={() => {
                  onOpenDeal(deal.id)
                }}
              >
                {detailLabel}
                <IconChevron />
              </button>
            </div>
          </article>
        ))
      )}
    </section>
  )
}
