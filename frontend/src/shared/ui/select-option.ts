/**
 * The `{ value, label }` pair a screen builds its `<option>` list from.
 *
 * Used to live on the local `Select` component. The kit's `Select` takes `<option>`
 * children instead of an options array, so the component went but this shape stayed —
 * it is data the screens assemble (stage lists, role lists), not a UI concern (#225).
 */
export interface SelectOption {
  value: string
  label: string
}
