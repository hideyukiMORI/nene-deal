import { useState } from 'react'
import type { CreateStageInput, PipelineStage, UpdateStageInput } from '@/entities/pipeline-stage'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Button, EmptyState, Input, Stack, Text } from '@/shared/ui'
import type { StageManagementStatus } from '../hooks/use-stage-management-page'
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
    <main className="mx-auto flex max-w-3xl flex-col gap-stack-lg px-inline-lg py-stack-lg">
      <header className="flex flex-col gap-stack-sm">
        <Text as="h1" variant="heading-md">
          {t('stages.title')}
        </Text>
        <Text muted>{t('stages.subtitle')}</Text>
      </header>

      <Stack direction="horizontal" gap="sm">
        <Button
          onClick={() => {
            setFormOpen((o) => !o)
          }}
        >
          {t('stages.create.open')}
        </Button>
      </Stack>

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

      {status === 'loading' ? <Text muted>{t('stages.loading')}</Text> : null}

      {status === 'error' ? (
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {t('stages.error.title')}
          </Text>
          <Text muted>{errorKey !== null ? t(errorKey) : t('common.error.unknown')}</Text>
          <Button variant="secondary" onClick={retry}>
            {t('common.actions.retry')}
          </Button>
        </Stack>
      ) : null}

      {status === 'ready' && stages.length === 0 ? (
        <EmptyState title={t('stages.empty.title')} description={t('stages.empty.description')} />
      ) : null}

      {status === 'ready' && stages.length > 0 ? (
        <ul className="flex flex-col gap-stack-sm">
          {stages.map((stage) => (
            <li
              key={stage.id}
              className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
            >
              {editingId === stage.id ? (
                <StageEditRow
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
              )}
            </li>
          ))}
        </ul>
      ) : null}
    </main>
  )
}

interface StageReadRowProps {
  stage: PipelineStage
  onEdit: () => void
  onDelete: () => void
}

function StageReadRow({ stage, onEdit, onDelete }: StageReadRowProps) {
  const { t } = useTranslation()

  return (
    <div className="flex items-center justify-between gap-inline-md">
      <div className="flex flex-col gap-stack-xs">
        <div className="flex items-center gap-inline-sm">
          <Text as="span" className="font-medium">
            {stage.label}
          </Text>
          {stage.isWon ? (
            <span className="rounded-sm bg-green-100 px-1.5 py-0.5 font-sans text-caption text-green-800">
              {t('stages.badge.won')}
            </span>
          ) : stage.isTerminal ? (
            <span className="rounded-sm bg-gray-100 px-1.5 py-0.5 font-sans text-caption text-gray-700">
              {t('stages.badge.terminal')}
            </span>
          ) : null}
        </div>
        <Text as="span" variant="caption" muted>
          {stage.slug} · order {stage.sortOrder}
        </Text>
      </div>

      {!stage.isTerminal ? (
        <Stack direction="horizontal" gap="xs">
          <Button variant="secondary" size="sm" onClick={onEdit}>
            {t('common.actions.edit')}
          </Button>
          <Button variant="secondary" size="sm" onClick={onDelete}>
            ×
          </Button>
        </Stack>
      ) : null}
    </div>
  )
}

interface StageEditRowProps {
  stage: PipelineStage
  onSave: (input: UpdateStageInput) => Promise<void>
  onCancel: () => void
}

function StageEditRow({ stage, onSave, onCancel }: StageEditRowProps) {
  const { t } = useTranslation()
  const [label, setLabel] = useState(stage.label)
  const [sortOrder, setSortOrder] = useState(String(stage.sortOrder))

  const handleSave = async () => {
    const order = parseInt(sortOrder, 10)
    if (isNaN(order) || order < 0) return
    await onSave({ label: label.trim() || stage.label, sortOrder: order })
  }

  return (
    <Stack gap="sm">
      <Input
        id={`stage-label-edit-${stage.id}`}
        label={t('stages.field.label')}
        value={label}
        onChange={(e) => {
          setLabel(e.target.value)
        }}
      />
      <Input
        id={`stage-sort-edit-${stage.id}`}
        label={t('stages.field.sortOrder')}
        type="number"
        value={sortOrder}
        onChange={(e) => {
          setSortOrder(e.target.value)
        }}
      />
      <Stack direction="horizontal" gap="sm">
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
      </Stack>
    </Stack>
  )
}
