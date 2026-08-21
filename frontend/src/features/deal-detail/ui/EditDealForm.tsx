import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { Deal, UpdateDealInput } from '@/entities/deal'
import { useTranslation } from '@/shared/i18n'
import { Button } from '@/shared/ui/primitives/Button'
import { Input } from '@/shared/ui/primitives/Input'
import { Stack } from '@/shared/ui/primitives/Stack'
import { Text } from '@/shared/ui/primitives/Text'
export interface EditDealFormProps {
  deal: Deal
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: UpdateDealInput) => Promise<boolean>
}

interface EditDealFormValues {
  accountLabel: string
  /** Whole yen as entered by the user; converted to cents on submit. */
  amountYen: number
  probabilityPercent: number
  note: string
  expectedCloseDate: string
}

export function EditDealForm({ deal, pending, errorMessage, onSubmit }: EditDealFormProps) {
  const { t } = useTranslation()

  const schema = z.object({
    accountLabel: z.string().trim().min(1, t('deal.validation.accountLabelRequired')),
    amountYen: z
      .number({ message: t('deal.validation.amountPositive') })
      .int(t('deal.validation.amountPositive'))
      .min(0, t('deal.validation.amountPositive')),
    probabilityPercent: z
      .number({ message: t('deal.validation.probabilityRange') })
      .int(t('deal.validation.probabilityRange'))
      .min(0, t('deal.validation.probabilityRange'))
      .max(100, t('deal.validation.probabilityRange')),
    note: z.string(),
    expectedCloseDate: z.string(),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<EditDealFormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      accountLabel: deal.accountLabel,
      amountYen: deal.amountCents,
      probabilityPercent: deal.probabilityPercent,
      note: deal.note ?? '',
      expectedCloseDate: deal.expectedCloseDate ?? '',
    },
  })

  const submit = handleSubmit(async (values) => {
    await onSubmit({
      accountLabel: values.accountLabel,
      amountCents: values.amountYen,
      probabilityPercent: values.probabilityPercent,
      note: values.note.trim() === '' ? null : values.note,
      expectedCloseDate: values.expectedCloseDate.trim() === '' ? null : values.expectedCloseDate,
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
        <div className="flex items-center gap-4 flex-wrap">
          <div className="flex-1">
            <Input
              id="edit-amount"
              label={t('deal.field.amount')}
              type="number"
              min={0}
              step={1}
              error={errors.amountYen?.message}
              {...register('amountYen', { valueAsNumber: true })}
            />
          </div>
          <div className="flex-1">
            <Input
              id="edit-probability"
              label={t('deal.field.probability')}
              type="number"
              min={0}
              max={100}
              error={errors.probabilityPercent?.message}
              {...register('probabilityPercent', { valueAsNumber: true })}
            />
          </div>
        </div>
        <Input
          id="edit-expected-close-date"
          label={t('deal.field.expectedCloseDate')}
          type="date"
          error={errors.expectedCloseDate?.message}
          {...register('expectedCloseDate')}
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
