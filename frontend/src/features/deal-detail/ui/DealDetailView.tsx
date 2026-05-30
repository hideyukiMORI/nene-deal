import type { Deal, InvoiceHandoffResult, UpdateDealInput } from '@/entities/deal'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import type { DealDetailStatus } from '../hooks/use-deal-detail-page'
import { EditDealForm } from './EditDealForm'

export interface DealDetailViewProps {
  status: DealDetailStatus
  errorMessageKey: MessageKey | null
  deal: Deal | null
  onBack: () => void
  submitEdit: (input: UpdateDealInput) => Promise<boolean>
  editPending: boolean
  editErrorKey: MessageKey | null
  handoff: () => Promise<boolean>
  handoffPending: boolean
  handoffErrorKey: MessageKey | null
  handoffResult: InvoiceHandoffResult | null
}

export function DealDetailView({
  status,
  errorMessageKey,
  deal,
  onBack,
  submitEdit,
  editPending,
  editErrorKey,
  handoff,
  handoffPending,
  handoffErrorKey,
  handoffResult,
}: DealDetailViewProps) {
  const { t, locale } = useTranslation()

  return (
    <main className="mx-auto flex max-w-3xl flex-col gap-stack-lg px-inline-lg py-stack-lg">
      <Stack direction="horizontal" gap="sm">
        <Button variant="secondary" size="sm" onClick={onBack}>
          {t('common.actions.back')}
        </Button>
      </Stack>

      {status === 'loading' ? <Text muted>{t('detail.loading')}</Text> : null}

      {status === 'missing' ? <EmptyState title={t('detail.notFound')} /> : null}

      {status === 'error' ? (
        <Stack gap="sm">
          <Text as="h1" variant="heading-md">
            {t('detail.error.title')}
          </Text>
          <Text muted>
            {errorMessageKey !== null ? t(errorMessageKey) : t('common.error.unknown')}
          </Text>
        </Stack>
      ) : null}

      {status === 'ready' && deal !== null ? (
        <Stack gap="lg">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {deal.accountLabel}
            </Text>
            <Text muted>
              {deal.stageSlug ?? ''} · {formatMoneyJpy(deal.amountCents, locale)} ·{' '}
              {String(deal.probabilityPercent)}%
            </Text>
            <Text variant="caption" muted>
              {deal.note ?? t('detail.note.empty')}
            </Text>
          </Stack>

          <EditDealForm
            deal={deal}
            pending={editPending}
            errorMessage={editErrorKey !== null ? t(editErrorKey) : null}
            onSubmit={submitEdit}
          />

          <HandoffSection
            deal={deal}
            handoff={handoff}
            handoffPending={handoffPending}
            handoffErrorMessage={handoffErrorKey !== null ? t(handoffErrorKey) : null}
            handoffResult={handoffResult}
          />
        </Stack>
      ) : null}
    </main>
  )
}

interface HandoffSectionProps {
  deal: Deal
  handoff: () => Promise<boolean>
  handoffPending: boolean
  handoffErrorMessage: string | null
  handoffResult: InvoiceHandoffResult | null
}

function HandoffSection({
  deal,
  handoff,
  handoffPending,
  handoffErrorMessage,
  handoffResult,
}: HandoffSectionProps) {
  const { t } = useTranslation()

  const clientId = handoffResult?.invoiceClientId ?? deal.invoiceClientId
  const quoteId = handoffResult?.invoiceQuoteId ?? deal.invoiceQuoteId
  const linked = quoteId !== null

  return (
    <section
      aria-label={t('handoff.title')}
      className="flex flex-col gap-stack-sm rounded-md border border-border bg-surface-raised px-inline-lg py-stack-lg shadow-sm"
    >
      <Text as="h2" variant="heading-sm">
        {t('handoff.title')}
      </Text>

      {linked ? (
        <Stack gap="xs">
          <Text className="text-ok">{t('handoff.linked')}</Text>
          <Text variant="caption" muted>
            {t('handoff.clientId')}: {String(clientId)} · {t('handoff.quoteId')}: {String(quoteId)}
          </Text>
        </Stack>
      ) : (
        <Stack gap="sm">
          <Text muted>{t('handoff.description')}</Text>
          {handoffErrorMessage !== null ? (
            <Text variant="caption" className="text-danger">
              {handoffErrorMessage}
            </Text>
          ) : null}
          <Stack direction="horizontal" gap="sm">
            <Button
              disabled={handoffPending}
              onClick={() => {
                void handoff()
              }}
            >
              {t('handoff.send')}
            </Button>
          </Stack>
        </Stack>
      )}
    </section>
  )
}
