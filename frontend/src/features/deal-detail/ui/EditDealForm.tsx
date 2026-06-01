import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { Deal, UpdateDealInput } from '@/entities/deal'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

export interface EditDealFormProps {
  deal: Deal
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: UpdateDealInput) => Promise<boolean>
}

interface EditDealFormValues {
  accountLabel: string
  amountCents: number
  probabilityPercent: number
  note: string
}

export function EditDealForm({ deal, pending, errorMessage, onSubmit }: EditDealFormProps) {
  const { t } = useTranslation()

  const schema = z.object({
    accountLabel: z.string().trim().min(1, t('deal.validation.accountLabelRequired')),
    amountCents: z
      .number({ message: t('deal.validation.amountPositive') })
      .int(t('deal.validation.amountPositive'))
      .min(0, t('deal.validation.amountPositive')),
    probabilityPercent: z
      .number({ message: t('deal.validation.probabilityRange') })
      .int(t('deal.validation.probabilityRange'))
      .min(0, t('deal.validation.probabilityRange'))
      .max(100, t('deal.validation.probabilityRange')),
    note: z.string(),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<EditDealFormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      accountLabel: deal.accountLabel,
      amountCents: deal.amountCents,
      probabilityPercent: deal.probabilityPercent,
      note: deal.note ?? '',
    },
  })

  const submit = handleSubmit(async (values) => {
    await onSubmit({
      accountLabel: values.accountLabel,
      amountCents: values.amountCents,
      probabilityPercent: values.probabilityPercent,
      note: values.note.trim() === '' ? null : values.note,
    })
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
          {t('detail.edit.title')}
        </Text>

        {errorMessage !== null ? (
          <Text variant="caption" className="danger">
            {errorMessage}
          </Text>
        ) : null}

        <Input
          id="edit-account-label"
          label={t('deal.field.accountLabel')}
          error={errors.accountLabel?.message}
          {...register('accountLabel')}
        />
        <Input
          id="edit-amount"
          label={t('deal.field.amount')}
          type="number"
          min={0}
          error={errors.amountCents?.message}
          {...register('amountCents', { valueAsNumber: true })}
        />
        <Input
          id="edit-probability"
          label={t('deal.field.probability')}
          type="number"
          min={0}
          max={100}
          error={errors.probabilityPercent?.message}
          {...register('probabilityPercent', { valueAsNumber: true })}
        />
        <Input id="edit-note" label={t('deal.field.note')} {...register('note')} />

        <Stack direction="horizontal" gap="sm">
          <Button type="submit" disabled={pending}>
            {t('common.actions.save')}
          </Button>
        </Stack>
      </Stack>
    </form>
  )
}
