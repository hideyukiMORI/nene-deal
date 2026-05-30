import type { DealId } from './ids'

export const dealKeys = {
  all: ['deals'] as const,
  details: () => [...dealKeys.all, 'detail'] as const,
  detail: (id: DealId) => [...dealKeys.details(), id] as const,
}
