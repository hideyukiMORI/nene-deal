import { useState } from 'react'
import type { KanbanColumn } from '@/entities/board'
import type { CreateDealInput } from '@/entities/deal'
import type { ForecastSummary } from '@/entities/forecast'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { EmptyState } from '@/shared/ui/components/EmptyState'
import { type SelectOption } from '@/shared/ui/primitives/Select'
import { useToast } from '@/shared/ui/toast/use-toast'
import { IconAccount, IconBack, IconCheck, IconChevron, IconEye, IconPlus } from '@/shared/ui/icons'
import { useKanbanDnd } from '../model/use-kanban-dnd'
import type { BoardStatus } from '../model/use-kanban-board-page'
import { CreateDealForm } from './CreateDealForm'

export interface KanbanBoardViewProps {
  status: BoardStatus
  errorMessageKey: MessageKey | null
  columns: KanbanColumn[]
  forecast: ForecastSummary | null
  stageOptions: SelectOption[]
  includeTerminal: boolean
  toggleTerminal: () => void
  includeDeleted: boolean
  toggleDeleted: () => void
  retry: () => void
  submitCreateDeal: (input: CreateDealInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  moveDeal: (dealId: string, toStageRef: string) => Promise<void>
  restoreDeal: (dealId: string) => Promise<void>
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
    <span className="text-text-muted inline-flex items-center gap-1.5 font-mono text-caption">
      <span className="bg-border h-1 w-9 overflow-hidden rounded-full">
        <span
          className="bg-accent block h-full rounded-full"
          style={{ width: `${String(value)}%` }}
        />
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
  includeTerminal,
  toggleTerminal,
  includeDeleted,
  toggleDeleted,
  retry,
  submitCreateDeal,
  createPending,
  createErrorKey,
  moveDeal,
  restoreDeal,
  onOpenDeal,
}: KanbanBoardViewProps) {
  const { t, locale } = useTranslation()
  const toast = useToast()
  const [formOpen, setFormOpen] = useState(false)

  const handleRestore = (dealId: string, accountLabel: string): void => {
    void (async () => {
      try {
        await restoreDeal(dealId)
        toast.success(t('toast.restored.title'), accountLabel)
      } catch {
        toast.error(t('toast.restore.error.title'))
      }
    })()
  }

  const handleMove = (dealId: string, _fromStageSlug: string, toStageSlug: string): void => {
    const deal = columns.flatMap((column) => column.deals).find((d) => d.id === dealId)
    const toColumn = columns.find((column) => column.stageSlug === toStageSlug)
    const stageLabel = toColumn?.stageLabel ?? toStageSlug
    const accountLabel = deal?.accountLabel ?? ''
    void (async () => {
      try {
        await moveDeal(dealId, toStageSlug)
        toast.success(t('toast.moved.title'), `${accountLabel}  →  ${stageLabel}`)
      } catch {
        toast.error(t('toast.move.error.title'))
      }
    })()
  }

  const { boardRef, onPointerDown } = useKanbanDnd(handleMove)

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
        <div className="row g4 wrap items-center">
          <div className="fchips">
            <span className="fchips-label">{t('board.show')}</span>
            <button
              type="button"
              className={`fchip${includeTerminal ? ' on' : ''}`}
              role="checkbox"
              aria-checked={includeTerminal}
              onClick={toggleTerminal}
            >
              <span className="fchip-box">
                <IconCheck />
              </span>
              <span className="fchip-label">{t('board.showTerminal')}</span>
            </button>
            <button
              type="button"
              className={`fchip fchip-plain${includeDeleted ? ' on' : ''}`}
              role="checkbox"
              aria-checked={includeDeleted}
              onClick={toggleDeleted}
            >
              <span className="fchip-box">
                <IconCheck />
              </span>
              <span className="fchip-ic">
                <IconEye />
              </span>
              <span className="fchip-label">{t('board.showDeleted')}</span>
            </button>
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
      </div>

      {forecast !== null ? (
        <section aria-label={t('forecast.title')} className="forecast">
          <Stat label={t('forecast.openDealCount')} value={String(forecast.openDealCount)} />
          <Stat
            label={t('forecast.pipelineTotal')}
            value={formatMoneyJpy(forecast.pipelineTotalCents, locale)}
            delta={t('forecast.delta.allStages')}
          />
          <Stat
            label={t('forecast.weightedTotal')}
            value={formatMoneyJpy(forecast.weightedTotalCents, locale)}
            accent
            delta={t('forecast.delta.weighted')}
            deltaOk
          />
          <Stat
            label={t('forecast.wonThisMonth')}
            value={formatMoneyJpy(
              forecast.byStage.find((bucket) => bucket.slug === 'won')?.totalCents ?? 0,
              locale,
            )}
            delta={t('forecast.delta.wonCount', {
              count: forecast.byStage.find((bucket) => bucket.slug === 'won')?.dealCount ?? 0,
            })}
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
        <div className="board" ref={boardRef} onPointerDown={onPointerDown}>
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
              deletedBadgeLabel={t('board.deletedBadge')}
              restoreLabel={t('board.restore')}
              dragHint={t('board.dragHint')}
              onOpenDeal={onOpenDeal}
              onRestore={handleRestore}
            />
          ))}
        </div>
      ) : null}
    </section>
  )
}

function Stat({
  label,
  value,
  accent,
  delta,
  deltaOk,
}: {
  label: string
  value: string
  accent?: boolean
  delta?: string
  deltaOk?: boolean
}) {
  return (
    <div className="stat">
      <span className="label">{label}</span>
      <span className="val" style={accent === true ? { color: 'var(--accent)' } : undefined}>
        {value}
      </span>
      {delta !== undefined ? (
        <span className={`delta ${deltaOk === true ? 'ok' : 'muted'}`}>{delta}</span>
      ) : null}
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
  deletedBadgeLabel: string
  restoreLabel: string
  dragHint: string
  onOpenDeal: (dealId: string) => void
  onRestore: (dealId: string, accountLabel: string) => void
}

function BoardColumn({
  column,
  moneyLocale,
  summaryLabel,
  emptyLabel,
  detailLabel,
  wonBadgeLabel,
  deletedBadgeLabel,
  restoreLabel,
  dragHint,
  onOpenDeal,
  onRestore,
}: BoardColumnProps) {
  const isWon = column.stageSlug === 'won'
  const isLost = column.stageSlug === 'lost'

  return (
    <section className="col" data-stage={column.stageSlug} aria-label={column.stageLabel}>
      <div className="flex flex-col gap-0.5">
        <div className="col-head">
          <h2 className="flex items-center gap-2 font-semibold whitespace-nowrap text-body">
            <span className="stage-dot" style={{ background: stageColor(column.stageSlug) }} />
            {column.stageLabel}
          </h2>
          <span className="bg-surface-overlay border-border text-text-muted rounded-full border px-2 py-px font-mono text-caption">
            {column.dealCount}
          </span>
        </div>
        <span className="text-text-faint px-1 text-caption">{summaryLabel}</span>
      </div>

      <div className="col-body">
        {column.deals.map((deal) => {
          const isDeleted = Boolean(deal.deletedAt)

          return (
            <article
              key={deal.id}
              className={`deal${isDeleted ? ' deal-deleted' : ''}`}
              // Deleted cards carry no data-deal, so drag & drop skips them.
              data-deal={isDeleted ? undefined : deal.id}
              data-won={isWon ? '1' : undefined}
              data-lost={isLost ? '1' : undefined}
              title={isDeleted ? undefined : dragHint}
            >
              {isDeleted ? null : (
                <span className="deal-grip" aria-hidden="true">
                  <i />
                  <i />
                  <i />
                  <i />
                  <i />
                  <i />
                </span>
              )}
              <div className="row between g2">
                <span className="acct">{deal.accountLabel}</span>
                {isDeleted ? (
                  <span className="badge badge-muted">{deletedBadgeLabel}</span>
                ) : isWon ? (
                  <span className="badge badge-ok">
                    <span className="dot" />
                    {wonBadgeLabel}
                  </span>
                ) : null}
              </div>
              <span className="amt">{formatMoneyJpy(deal.amountCents, moneyLocale)}</span>
              {deal.ownerLabel !== null ? (
                <span className="text-text-faint inline-flex min-w-0 items-center gap-1 overflow-hidden text-caption text-ellipsis whitespace-nowrap [&>svg]:size-3 [&>svg]:flex-none">
                  <IconAccount />
                  {deal.ownerLabel}
                </span>
              ) : null}
              <div className="mt-0.5 flex items-center justify-between gap-2">
                <ProbBar value={deal.probabilityPercent} />
                {isDeleted ? (
                  <button
                    type="button"
                    className="row g1 faint t-tiny cursor-pointer"
                    data-details-link
                    style={{
                      border: 'none',
                      background: 'none',
                      cursor: 'pointer',
                      font: 'inherit',
                    }}
                    onClick={() => {
                      onRestore(deal.id, deal.accountLabel)
                    }}
                  >
                    <IconBack />
                    {restoreLabel}
                  </button>
                ) : (
                  <button
                    type="button"
                    className="row g1 faint t-tiny cursor-pointer"
                    data-details-link
                    style={{
                      border: 'none',
                      background: 'none',
                      cursor: 'pointer',
                      font: 'inherit',
                    }}
                    onClick={() => {
                      onOpenDeal(deal.id)
                    }}
                  >
                    {detailLabel}
                    <IconChevron />
                  </button>
                )}
              </div>
            </article>
          )
        })}
        <div className="col-empty">{emptyLabel}</div>
      </div>
    </section>
  )
}
