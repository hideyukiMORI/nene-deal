import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateStageInput } from '@/entities/pipeline-stage'
import { useTranslation } from '@/shared/i18n'
import { Button } from '@/shared/ui/primitives/Button'
import { Input } from '@/shared/ui/primitives/Input'
import { Stack } from '@/shared/ui/primitives/Stack'
import { Text } from '@/shared/ui/primitives/Text'
interface CreateStageFormProps {
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: CreateStageInput) => Promise<boolean>
  onCancel: () => void
}

interface FormValues {
  label: string
  sortOrder: number
}

export function CreateStageForm({
  pending,
  errorMessage,
  onSubmit,
  onCancel,
}: CreateStageFormProps) {
  const { t } = useTranslation()

  const schema = z.object({
    label: z.string().trim().min(1, t('stages.validation.labelRequired')).max(64),
    sortOrder: z.number().int().min(0, t('stages.validation.sortOrderNonNeg')),
  })

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { label: '', sortOrder: 10 },
  })

  const submit = handleSubmit(async (values) => {
    const ok = await onSubmit({ label: values.label, sortOrder: values.sortOrder })
    if (ok) reset()
  })

  return (
    <form
      noValidate
      onSubmit={(e) => {
        void submit(e)
      }}
      className="card card-pad"
    >
      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('stages.create.title')}
        </Text>

        {errorMessage !== null ? (
          <Text variant="caption" className="danger">
            {errorMessage}
          </Text>
        ) : null}

        <Input
          id="stage-label"
          label={t('stages.field.label')}
          error={errors.label?.message}
          {...register('label')}
        />
        <Input
          id="stage-sort-order"
          label={t('stages.field.sortOrder')}
          type="number"
          error={errors.sortOrder?.message}
          {...register('sortOrder', { valueAsNumber: true })}
        />

        <Stack direction="horizontal" gap="sm">
          <Button type="submit" disabled={pending}>
            {t('stages.create.submit')}
          </Button>
          <Button type="button" variant="secondary" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </Stack>
      </Stack>
    </form>
  )
}
