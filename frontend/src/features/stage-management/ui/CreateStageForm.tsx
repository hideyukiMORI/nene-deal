import { zodResolver } from '@hookform/resolvers/zod'
import { Button, FormField, Input, Stack } from '@hideyukimori/nene2-ui'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateStageInput } from '@/entities/pipeline-stage'
import { useTranslation } from '@/shared/i18n'
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
      {/* gap: local `sm` was gap-2 = 8px → kit `2xs` (0.5rem). 名前が2段ずれる（#225） */}
      <Stack gap="2xs">
        {/* park（板 L15）: 見出し・caption の legacy タイポは契約語彙に無い（21/16/12.5px・
            leading 1.2/1.25・tracking はトークン0本）。utility 化は arbitrary value になる。 */}
        <h2 className="t-h2">{t('stages.create.title')}</h2>

        {errorMessage !== null ? <p className="t-cap danger">{errorMessage}</p> : null}

        <FormField
          id="stage-label"
          label={t('stages.field.label')}
          error={errors.label?.message ?? null}
        >
          <Input {...register('label')} />
        </FormField>
        <FormField
          id="stage-sort-order"
          label={t('stages.field.sortOrder')}
          error={errors.sortOrder?.message ?? null}
        >
          <Input type="number" {...register('sortOrder', { valueAsNumber: true })} />
        </FormField>

        {/* align="center": ローカル Stack は horizontal で常に items-center だったが、
            キットは align を渡した時だけ付く（#225 の地雷） */}
        <Stack direction="horizontal" align="center" gap="2xs">
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
