import { Button, FormField, Input, Select, Stack } from '@hideyukimori/nene2-ui'
import type { SelectOption } from '@/shared/ui/select-option'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateDealInput } from '@/entities/deal'
import { useTranslation } from '@/shared/i18n'
export interface CreateDealFormProps {
  stageOptions: SelectOption[]
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: CreateDealInput) => Promise<boolean>
  onCancel: () => void
}

interface CreateDealFormValues {
  accountLabel: string
  /** Whole yen as entered by the user; converted to cents on submit. */
  amountYen: number
  stageRef: string
  probabilityPercent: number
  expectedCloseDate: string
}

export function CreateDealForm({
  stageOptions,
  pending,
  errorMessage,
  onSubmit,
  onCancel,
}: CreateDealFormProps) {
  const { t } = useTranslation()

  const schema = z.object({
    accountLabel: z.string().trim().min(1, t('deal.validation.accountLabelRequired')),
    amountYen: z
      .number({ message: t('deal.validation.amountPositive') })
      .int(t('deal.validation.amountPositive'))
      .min(0, t('deal.validation.amountPositive')),
    stageRef: z.string().min(1),
    probabilityPercent: z
      .number({ message: t('deal.validation.probabilityRange') })
      .int(t('deal.validation.probabilityRange'))
      .min(0, t('deal.validation.probabilityRange'))
      .max(100, t('deal.validation.probabilityRange')),
    expectedCloseDate: z.string(),
  })

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<CreateDealFormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      accountLabel: '',
      amountYen: 0,
      stageRef: stageOptions[0]?.value ?? '',
      probabilityPercent: 50,
      expectedCloseDate: '',
    },
  })

  const submit = handleSubmit(async (values) => {
    const created = await onSubmit({
      accountLabel: values.accountLabel,
      amountCents: values.amountYen,
      stageRef: values.stageRef,
      probabilityPercent: values.probabilityPercent,
      expectedCloseDate: values.expectedCloseDate.trim() === '' ? null : values.expectedCloseDate,
    })
    if (created) {
      reset()
    }
  })

  return (
    <form
      noValidate
      onSubmit={(event) => {
        void submit(event)
      }}
      className="card card-pad"
    >
      <Stack gap="sm">
        <h2 className="t-h2">{t('deal.create.title')}</h2>

        {errorMessage !== null ? <p className="t-cap danger">{errorMessage}</p> : null}

        <FormField
          id="deal-account-label"
          label={t('deal.field.accountLabel')}
          error={errors.accountLabel?.message ?? null}
        >
          <Input {...register('accountLabel')} />
        </FormField>
        <div className="flex items-center gap-4 flex-wrap">
          <div className="flex-1">
            <FormField
              id="deal-amount"
              label={t('deal.field.amount')}
              error={errors.amountYen?.message ?? null}
            >
              <Input
                type="number"
                min={0}
                step={1}
                {...register('amountYen', { valueAsNumber: true })}
              />
            </FormField>
          </div>
          <div className="flex-1">
            <FormField
              id="deal-probability"
              label={t('deal.field.probability')}
              error={errors.probabilityPercent?.message ?? null}
            >
              <Input
                type="number"
                min={0}
                max={100}
                {...register('probabilityPercent', { valueAsNumber: true })}
              />
            </FormField>
          </div>
        </div>
        <FormField
          id="deal-stage"
          label={t('deal.field.stage')}
          error={errors.stageRef?.message ?? null}
        >
          <Select {...register('stageRef')}>
            {stageOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
        </FormField>
        <FormField
          id="deal-expected-close-date"
          label={t('deal.field.expectedCloseDate')}
          error={errors.expectedCloseDate?.message ?? null}
        >
          <Input type="date" {...register('expectedCloseDate')} />
        </FormField>

        <Stack direction="horizontal" align="center" gap="2xs">
          <Button type="submit" disabled={pending}>
            {t('deal.create.submit')}
          </Button>
          <Button type="button" variant="secondary" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </Stack>
      </Stack>
    </form>
  )
}
