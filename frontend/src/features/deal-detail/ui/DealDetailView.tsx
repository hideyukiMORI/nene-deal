import type { Deal, DealActivity, InvoiceHandoffResult, UpdateDealInput } from '@/entities/deal'
import type { PipelineStage } from '@/entities/pipeline-stage'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { EmptyState, Select, useToast } from '@/shared/ui'
import { IconBack, IconCheck, IconInvoice, IconOwner, IconTrash } from '@/shared/ui/icons'
import type { DealDetailStatus } from '../hooks/use-deal-detail-page'
import { ActivityTimeline } from './ActivityTimeline'
import { EditDealForm } from './EditDealForm'

export interface DealDetailViewProps {
  status: DealDetailStatus
  errorMessageKey: MessageKey | null
  deal: Deal | null
  onBack: () => void
  onDeleted: () => void
  submitEdit: (input: UpdateDealInput) => Promise<boolean>
  editPending: boolean
  editErrorKey: MessageKey | null
  handoff: () => Promise<boolean>
  handoffPending: boolean
  handoffErrorKey: MessageKey | null
  handoffResult: InvoiceHandoffResult | null
  stages: PipelineStage[]
  changeStage: (toStageId: string) => Promise<boolean>
  stagePending: boolean
  deleteDeal: () => Promise<boolean>
  deletePending: boolean
  activity: DealActivity[]
}

export function DealDetailView({
  status,
  errorMessageKey,
  deal,
  onBack,
  onDeleted,
  submitEdit,
  editPending,
  editErrorKey,
  handoff,
  handoffPending,
  handoffErrorKey,
  handoffResult,
  stages,
  changeStage,
  stagePending,
  deleteDeal,
  deletePending,
  activity,
}: DealDetailViewProps) {
  const { t, locale } = useTranslation()
  const toast = useToast()

  return (
    <section className="content content-narrow stack g6">
      <button
        type="button"
        className="btn btn-secondary btn-sm back-link"
        onClick={onBack}
        style={{ alignSelf: 'flex-start', paddingLeft: 6 }}
      >
        <IconBack />
        {t('detail.back')}
      </button>

      {status === 'loading' ? <p className="muted t-body">{t('detail.loading')}</p> : null}

      {status === 'missing' ? <EmptyState title={t('detail.notFound')} /> : null}

      {status === 'error' ? (
        <div className="stack g3">
          <h1 className="t-h1">{t('detail.error.title')}</h1>
          <p className="muted t-body">
            {errorMessageKey !== null ? t(errorMessageKey) : t('common.error.unknown')}
          </p>
        </div>
      ) : null}

      {status === 'ready' && deal !== null ? (
        <>
          <div className="stack g3 page-head">
            <div className="row between wrap g3">
              <h1 className="t-h1">{deal.accountLabel}</h1>
              {deal.stageSlug === 'won' ? (
                <span className="badge badge-ok">
                  <span className="dot" />
                  {t('stages.badge.won')}
                </span>
              ) : null}
            </div>
            <div className="row g4 wrap muted t-cap">
              <span className="num semi" style={{ color: 'var(--fg)', fontSize: 15 }}>
                {formatMoneyJpy(deal.amountCents, locale)}
              </span>
              <span>
                {t('deal.field.probability')}{' '}
                <span className="num">{deal.probabilityPercent}%</span>
              </span>
              <span className="row g1">
                <IconOwner />
                <span className="num">{deal.ownerLabel ?? t('detail.owner.empty')}</span>
              </span>
              {deal.expectedCloseDate !== null ? (
                <span>
                  {t('deal.field.expectedCloseDate')}{' '}
                  <span className="num">{deal.expectedCloseDate}</span>
                </span>
              ) : null}
            </div>
            <p className="muted t-cap">{deal.note ?? t('detail.note.empty')}</p>
            {stages.length > 0 ? (
              <div style={{ maxWidth: 240 }}>
                <Select
                  id="detail-stage"
                  label={t('deal.field.stage')}
                  options={stages.map((stage) => ({ value: stage.id, label: stage.label }))}
                  value={deal.stageId}
                  disabled={stagePending}
                  onChange={(event) => {
                    const toStageId = event.target.value
                    const stageLabel = stages.find((stage) => stage.id === toStageId)?.label
                    void changeStage(toStageId).then((ok) => {
                      if (ok) toast.success(t('toast.moved.title'), stageLabel)
                    })
                  }}
                />
              </div>
            ) : null}
          </div>

          <EditDealForm
            deal={deal}
            pending={editPending}
            errorMessage={editErrorKey !== null ? t(editErrorKey) : null}
            onSubmit={async (input) => {
              const ok = await submitEdit(input)
              if (ok) toast.success(t('toast.saved.title'), t('toast.saved.sub'))
              return ok
            }}
          />

          <HandoffSection
            deal={deal}
            handoff={handoff}
            handoffPending={handoffPending}
            handoffErrorMessage={handoffErrorKey !== null ? t(handoffErrorKey) : null}
            handoffResult={handoffResult}
          />

          <ActivityTimeline activity={activity} stages={stages} locale={locale} />

          <div className="stack g2 danger-zone">
            <button
              type="button"
              className="btn btn-danger"
              disabled={deletePending}
              onClick={() => {
                if (!window.confirm(t('detail.delete.confirm'))) return
                void deleteDeal().then((ok) => {
                  if (ok) {
                    toast.success(t('toast.deleted.title'), deal.accountLabel)
                    onDeleted()
                  } else {
                    toast.error(t('toast.delete.error.title'))
                  }
                })
              }}
            >
              <IconTrash />
              {t('detail.delete.label')}
            </button>
            <span className="faint t-tiny">{t('detail.delete.hint')}</span>
          </div>
        </>
      ) : null}
    </section>
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
    <section aria-label={t('handoff.title')} className="card card-pad stack g4">
      <div className="row g3">
        <span className="handoff-mark">
          <IconInvoice />
        </span>
        <div className="stack g1">
          <h2 className="t-h2">{t('handoff.title')}</h2>
          <span className="muted t-cap">{t('handoff.description')}</span>
        </div>
      </div>
      <hr className="divider" />

      {linked ? (
        <div className="stack g2">
          <div className="row g2 ok semi t-cap">
            <IconCheck />
            {t('handoff.linked')}
          </div>
          <span className="faint t-tiny mono">
            {t('handoff.clientId')}: {String(clientId)} · {t('handoff.quoteId')}: {String(quoteId)}
          </span>
        </div>
      ) : (
        <div className="stack g3">
          {handoffErrorMessage !== null ? (
            <span className="t-cap danger">{handoffErrorMessage}</span>
          ) : null}
          <div className="row g3">
            <button
              type="button"
              className="btn btn-primary"
              disabled={handoffPending}
              onClick={() => {
                void handoff()
              }}
            >
              {t('handoff.send')}
            </button>
          </div>
        </div>
      )}

      <span className="faint t-tiny">{t('handoff.hint')}</span>
    </section>
  )
}
