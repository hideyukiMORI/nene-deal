declare const stageIdBrand: unique symbol

export type StageId = string & { readonly [stageIdBrand]: never }

export function toStageId(value: string): StageId {
  return value as StageId
}
