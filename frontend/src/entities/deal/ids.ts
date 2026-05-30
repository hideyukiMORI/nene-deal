declare const dealIdBrand: unique symbol

export type DealId = string & { readonly [dealIdBrand]: never }

export function toDealId(value: string): DealId {
  return value as DealId
}
