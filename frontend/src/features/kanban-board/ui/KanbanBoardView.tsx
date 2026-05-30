import { useState } from 'react'
import type { KanbanColumn } from '@/entities/board'
import type { CreateDealInput } from '@/entities/deal'
import type { ForecastSummary } from '@/entities/forecast'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { Button, EmptyState, Select, Stack, Text, type SelectOption } from '@/shared/ui'
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
  moveDeal,
  onOpenDeal,
}: KanbanBoardViewProps) {
  const { t, locale, setLocale } = useTranslation()
  const [formOpen, setFormOpen] = useState(false)

  return (
    <main className="mx-auto flex max-w-6xl flex-col gap-stack-lg px-inline-lg py-stack-lg">
      <header className="flex flex-col gap-stack-sm">
        <Stack direction="horizontal" gap="sm" className="justify-between">
          <div className="flex flex-col gap-stack-xs">
            <Text as="h1" variant="heading-md">
              {t('app.title')}
            </Text>
            <Text muted>{t('app.subtitle')}</Text>
          </div>
          <Stack direction="horizontal" gap="xs">
            {SUPPORTED_LOCALE_IDS.map((id) => (
              <Button
                key={id}
                size="sm"
                variant={locale === id ? 'primary' : 'secondary'}
                onClick={() => {
                  setLocale(id)
                }}
              >
                {LOCALES[id].label}
              </Button>
            ))}
          </Stack>
        </Stack>
      </header>

      {forecast !== null ? (
        <section
          aria-label={t('forecast.title')}
          className="flex flex-wrap gap-inline-lg rounded-md border border-border bg-surface-raised px-inline-lg py-stack-md shadow-sm"
        >
          <ForecastStat
            label={t('forecast.openDealCount')}
            value={String(forecast.openDealCount)}
          />
          <ForecastStat
            label={t('forecast.pipelineTotal')}
            value={formatMoneyJpy(forecast.pipelineTotalCents, locale)}
          />
          <ForecastStat
            label={t('forecast.weightedTotal')}
            value={formatMoneyJpy(forecast.weightedTotalCents, locale)}
          />
        </section>
      ) : null}

      <Stack direction="horizontal" gap="sm">
        <Button
          onClick={() => {
            setFormOpen((open) => !open)
          }}
        >
          {t('deal.create.open')}
        </Button>
      </Stack>

      {formOpen ? (
        <CreateDealForm
          stageOptions={stageOptions}
          pending={createPending}
          errorMessage={createErrorKey !== null ? t(createErrorKey) : null}
          onSubmit={async (input) => {
            const created = await submitCreateDeal(input)
            if (created) {
              setFormOpen(false)
            }
            return created
          }}
          onCancel={() => {
            setFormOpen(false)
          }}
        />
      ) : null}

      {status === 'loading' ? <Text muted>{t('board.loading')}</Text> : null}

      {status === 'error' ? (
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {t('board.error.title')}
          </Text>
          <Text muted>
            {errorMessageKey !== null ? t(errorMessageKey) : t('common.error.unknown')}
          </Text>
          <Stack direction="horizontal" gap="sm">
            <Button variant="secondary" onClick={retry}>
              {t('common.actions.retry')}
            </Button>
          </Stack>
        </Stack>
      ) : null}

      {status === 'ready' && columns.length === 0 ? (
        <EmptyState title={t('board.empty.title')} description={t('board.empty.description')} />
      ) : null}

      {status === 'ready' && columns.length > 0 ? (
        <div className="grid grid-cols-1 gap-inline-md md:grid-cols-2 xl:grid-cols-4">
          {columns.map((column) => (
            <BoardColumnCard
              key={column.stageId}
              column={column}
              stageOptions={stageOptions}
              moneyLocale={locale}
              summaryLabel={t('board.column.summary', {
                count: column.dealCount,
                weighted: formatMoneyJpy(column.weightedTotalCents, locale),
              })}
              emptyLabel={t('board.column.empty')}
              moveLabel={t('deal.field.stage')}
              detailLabel={t('deal.open.detail')}
              onMove={(dealId, toStageRef) => {
                void moveDeal(dealId, toStageRef)
              }}
              onOpenDeal={onOpenDeal}
            />
          ))}
        </div>
      ) : null}
    </main>
  )
}

interface ForecastStatProps {
  label: string
  value: string
}

function ForecastStat({ label, value }: ForecastStatProps) {
  return (
    <div className="flex flex-col gap-stack-xs">
      <Text variant="caption" muted>
        {label}
      </Text>
      <Text variant="heading-sm">{value}</Text>
    </div>
  )
}

interface BoardColumnCardProps {
  column: KanbanColumn
  stageOptions: SelectOption[]
  moneyLocale: string
  summaryLabel: string
  emptyLabel: string
  moveLabel: string
  detailLabel: string
  onMove: (dealId: string, toStageRef: string) => void
  onOpenDeal: (dealId: string) => void
}

function BoardColumnCard({
  column,
  stageOptions,
  moneyLocale,
  summaryLabel,
  emptyLabel,
  moveLabel,
  detailLabel,
  onMove,
  onOpenDeal,
}: BoardColumnCardProps) {
  return (
    <section
      aria-label={column.stageLabel}
      className="flex flex-col gap-stack-sm rounded-md border border-border bg-surface px-inline-md py-stack-md"
    >
      <div className="flex flex-col gap-stack-xs">
        <Text as="h2" variant="heading-sm">
          {column.stageLabel}
        </Text>
        <Text variant="caption" muted>
          {summaryLabel}
        </Text>
      </div>

      {column.deals.length === 0 ? (
        <Text variant="caption" muted>
          {emptyLabel}
        </Text>
      ) : (
        <ul className="flex flex-col gap-stack-sm">
          {column.deals.map((deal) => (
            <li
              key={deal.id}
              className="flex flex-col gap-stack-xs rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
            >
              <Text as="span" className="font-medium">
                {deal.accountLabel}
              </Text>
              <Text as="span" variant="caption" muted>
                {formatMoneyJpy(deal.amountCents, moneyLocale)} · {String(deal.probabilityPercent)}%
              </Text>
              <Select
                id={`move-${deal.id}`}
                label={moveLabel}
                labelHidden
                options={stageOptions}
                value={column.stageSlug}
                onChange={(event) => {
                  onMove(deal.id, event.target.value)
                }}
              />
              <Stack direction="horizontal" gap="xs">
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    onOpenDeal(deal.id)
                  }}
                >
                  {detailLabel}
                </Button>
              </Stack>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
