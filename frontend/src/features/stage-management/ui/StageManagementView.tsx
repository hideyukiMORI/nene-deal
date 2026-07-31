import { useState } from 'react'
import type { CreateStageInput, PipelineStage, UpdateStageInput } from '@/entities/pipeline-stage'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { EmptyState } from '@/shared/ui/components/EmptyState'
import { Button } from '@/shared/ui/primitives/Button'
import { Input } from '@/shared/ui/primitives/Input'
import { IconPlus } from '@/shared/ui/icons'
import type { StageManagementStatus } from '../model/use-stage-management-page'
import { CreateStageForm } from './CreateStageForm'

export interface StageManagementViewProps {
  status: StageManagementStatus
  errorKey: MessageKey | null
  stages: PipelineStage[]
  retry: () => void
  createStage: (input: CreateStageInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  updateStage: (stageId: string, input: UpdateStageInput) => Promise<boolean>
  deleteStage: (stageId: string) => Promise<boolean>
}

const STAGE_COLORS: Record<string, string> = {
  lead: 'var(--info)',
  qualified: 'var(--warn)',
  proposal: 'var(--accent)',
  won: 'var(--ok)',
  lost: 'var(--fg-faint)',
}

export function StageManagementView({
  status,
  errorKey,
  stages,
  retry,
  createStage,
  createPending,
  createErrorKey,
  updateStage,
  deleteStage,
}: StageManagementViewProps) {
  const { t } = useTranslation()
  const [formOpen, setFormOpen] = useState(false)
  const [editingId, setEditingId] = useState<string | null>(null)

  return (
    <section className="content content-narrow stack">
      <div className="row justify-between flex-wrap g4 page-head">
        <div className="stack gap-1">
          <h1 className="t-h1">{t('stages.title')}</h1>
          <span className="muted t-cap">{t('stages.subtitle')}</span>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          onClick={() => {
            setFormOpen((o) => !o)
          }}
        >
          <IconPlus />
          {t('stages.create.open')}
        </button>
      </div>

      {formOpen ? (
        <CreateStageForm
          pending={createPending}
          errorMessage={createErrorKey !== null ? t(createErrorKey) : null}
          onSubmit={async (input) => {
            const ok = await createStage(input)
            if (ok) setFormOpen(false)
            return ok
          }}
          onCancel={() => {
            setFormOpen(false)
          }}
        />
      ) : null}

      {status === 'loading' ? <p className="muted t-body">{t('stages.loading')}</p> : null}

      {status === 'error' ? (
        <div className="stack gap-3">
          <h2 className="t-h2">{t('stages.error.title')}</h2>
          <p className="muted t-body">
            {errorKey !== null ? t(errorKey) : t('common.error.unknown')}
          </p>
          <div className="row gap-3">
            <button type="button" className="btn btn-secondary" onClick={retry}>
              {t('common.actions.retry')}
            </button>
          </div>
        </div>
      ) : null}

      {status === 'ready' && stages.length === 0 ? (
        <EmptyState title={t('stages.empty.title')} description={t('stages.empty.description')} />
      ) : null}

      {status === 'ready' && stages.length > 0 ? (
        <div className="card">
          <div className="rows">
            {stages.map((stage) =>
              editingId === stage.id ? (
                <StageEditRow
                  key={stage.id}
                  stage={stage}
                  onSave={async (input) => {
                    const ok = await updateStage(stage.id, input)
                    if (ok) setEditingId(null)
                  }}
                  onCancel={() => {
                    setEditingId(null)
                  }}
                />
              ) : (
                <StageReadRow
                  key={stage.id}
                  stage={stage}
                  onEdit={() => {
                    setEditingId(stage.id)
                  }}
                  onDelete={() => {
                    if (window.confirm(t('stages.delete.confirm'))) {
                      void deleteStage(stage.id)
                    }
                  }}
                />
              ),
            )}
          </div>
        </div>
      ) : null}
    </section>
  )
}

function StageReadRow({
  stage,
  onEdit,
  onDelete,
}: {
  stage: PipelineStage
  onEdit: () => void
  onDelete: () => void
}) {
  const { t } = useTranslation()
  const color = STAGE_COLORS[stage.slug] ?? 'var(--fg-faint)'
  return (
    <div className="list-row">
      <div className="row gap-3">
        <span className="num faint" style={{ width: 18, textAlign: 'center' }}>
          {stage.sortOrder}
        </span>
        <span
          className="stage-dot"
          style={{ width: 9, height: 9, borderRadius: 2, background: color }}
        />
        <div className="stack gap-1">
          <span className="row gap-2">
            <span className="medium">{stage.label}</span>
            {stage.isWon ? (
              <span className="badge badge-ok">
                <span className="dot" />
                {t('stages.badge.won')}
              </span>
            ) : stage.isTerminal ? (
              <span className="badge badge-muted">{t('stages.badge.terminal')}</span>
            ) : null}
          </span>
          <span className="faint t-tiny mono">
            {stage.slug} · order {stage.sortOrder}
          </span>
        </div>
      </div>

      {stage.isTerminal ? null : (
        <div className="row gap-2">
          <Button variant="secondary" size="sm" onClick={onEdit}>
            {t('common.actions.edit')}
          </Button>
          <button
            type="button"
            className="icon-btn"
            title={t('stages.delete.confirm')}
            aria-label={t('common.actions.edit')}
            onClick={onDelete}
          >
            ×
          </button>
        </div>
      )}
    </div>
  )
}

function StageEditRow({
  stage,
  onSave,
  onCancel,
}: {
  stage: PipelineStage
  onSave: (input: UpdateStageInput) => Promise<void>
  onCancel: () => void
}) {
  const { t } = useTranslation()
  const [label, setLabel] = useState(stage.label)
  const [sortOrder, setSortOrder] = useState(String(stage.sortOrder))

  const handleSave = async () => {
    const order = parseInt(sortOrder, 10)
    if (isNaN(order) || order < 0) return
    await onSave({ label: label.trim() || stage.label, sortOrder: order })
  }

  return (
    <div className="list-row">
      <form
        className="stack gap-4 flex-1"
        onSubmit={(e) => {
          e.preventDefault()
        }}
      >
        <div className="row gap-2 t-cap semi" style={{ color: 'var(--accent)' }}>
          {t('stages.editing')}
        </div>
        <div className="row gap-4 flex-wrap">
          <div className="flex-1">
            <Input
              id={`stage-label-edit-${stage.id}`}
              label={t('stages.field.label')}
              value={label}
              onChange={(e) => {
                setLabel(e.target.value)
              }}
            />
          </div>
          <div style={{ width: 150 }}>
            <Input
              id={`stage-sort-edit-${stage.id}`}
              label={t('stages.field.sortOrder')}
              type="number"
              value={sortOrder}
              onChange={(e) => {
                setSortOrder(e.target.value)
              }}
            />
          </div>
        </div>
        <div className="row gap-2">
          <Button
            size="sm"
            onClick={() => {
              void handleSave()
            }}
          >
            {t('stages.edit.save')}
          </Button>
          <Button variant="secondary" size="sm" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      </form>
    </div>
  )
}
