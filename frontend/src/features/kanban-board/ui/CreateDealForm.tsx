import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateDealInput } from '@/entities/deal'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Select, Stack, Text, type SelectOption } from '@/shared/ui'

export interface CreateDealFormProps {
  stageOptions: SelectOption[]
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: CreateDealInput) => Promise<boolean>
  onCancel: () => void
}

interface CreateDealFormValues {
  accountLabel: string
  amountCents: number
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
    amountCents: z
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
      amountCents: 0,
      stageRef: stageOptions[0]?.value ?? '',
      probabilityPercent: 50,
      expectedCloseDate: '',
    },
  })

  const submit = handleSubmit(async (values) => {
    const created = await onSubmit({
      accountLabel: values.accountLabel,
      amountCents: values.amountCents,
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
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('deal.create.title')}
        </Text>

        {errorMessage !== null ? (
          <Text variant="caption" className="danger">
            {errorMessage}
          </Text>
        ) : null}

        <Input
          id="deal-account-label"
          label={t('deal.field.accountLabel')}
          error={errors.accountLabel?.message}
          {...register('accountLabel')}
        />
        <div className="row g4 wrap">
          <div className="grow">
            <Input
              id="deal-amount"
              label={t('deal.field.amount')}
              type="number"
              min={0}
              error={errors.amountCents?.message}
              {...register('amountCents', { valueAsNumber: true })}
            />
          </div>
          <div className="grow">
            <Input
              id="deal-probability"
              label={t('deal.field.probability')}
              type="number"
              min={0}
              max={100}
              error={errors.probabilityPercent?.message}
              {...register('probabilityPercent', { valueAsNumber: true })}
            />
          </div>
        </div>
        <Select
          id="deal-stage"
          label={t('deal.field.stage')}
          options={stageOptions}
          error={errors.stageRef?.message}
          {...register('stageRef')}
        />
        <Input
          id="deal-expected-close-date"
          label={t('deal.field.expectedCloseDate')}
          type="date"
          error={errors.expectedCloseDate?.message}
          {...register('expectedCloseDate')}
        />

        <Stack direction="horizontal" gap="sm">
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
