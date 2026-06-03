export type { DealId } from './ids'
export { toDealId } from './ids'
export type {
  CreateDealInput,
  Deal,
  DealActivity,
  DealActivityAction,
  DealActivityChange,
  InvoiceHandoffResult,
  UpdateDealInput,
} from './model'
export { dealKeys } from './query-keys'
export { useDeal, useDealActivity } from './queries'
export {
  useChangeDealStage,
  useCreateDeal,
  useDeleteDeal,
  useInvoiceHandoff,
  useRestoreDeal,
  useUpdateDeal,
} from './mutations'
export type { ChangeDealStageVars, UpdateDealVars } from './mutations'
