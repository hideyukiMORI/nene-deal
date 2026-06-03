export type ToastVariant = 'success' | 'error'

export interface Toast {
  id: number
  title: string
  sub?: string
  variant: ToastVariant
}

type Listener = (toasts: Toast[]) => void

/**
 * Module-level toast store. Deliberately provider-free so any component can
 * raise a toast via `useToast()` without a wrapping context — keeping existing
 * component tests (which only mount i18n + react-query) working unchanged.
 * A single `<Toaster />` mounted at the app root subscribes and renders.
 */
let toasts: Toast[] = []
const listeners = new Set<Listener>()
let nextId = 1

function emit(): void {
  for (const listener of listeners) {
    listener(toasts)
  }
}

export function subscribeToasts(listener: Listener): () => void {
  listeners.add(listener)
  listener(toasts)
  return () => {
    listeners.delete(listener)
  }
}

export function dismissToast(id: number): void {
  toasts = toasts.filter((toast) => toast.id !== id)
  emit()
}

export function pushToast(title: string, sub: string | undefined, variant: ToastVariant): number {
  const id = nextId++
  toasts = [...toasts, { id, title, sub, variant }]
  emit()
  return id
}
