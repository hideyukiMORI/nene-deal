import { IconGlobe } from '@/shared/ui/icons'

export interface LangToggleItem {
  /** Locale id (e.g. 'ja', 'en'). */
  id: string
  /** Display label — endonym or short code, resolved by the consumer. */
  label: string
}

export interface LangToggleProps {
  /** Selectable locales, in display order. */
  items: readonly LangToggleItem[]
  /** Currently active locale id. */
  activeId: string
  /** Called with the locale id to activate. */
  onSelect: (id: string) => void
  /** Accessible group title (e.g. t('shell.lang')). */
  groupLabel: string
}

/**
 * Globe + language toggle (presentation-only). The active locale, selectable
 * items, and labels are supplied by the consumer, which owns i18n (R1②:
 * shared/ui does not import i18n). Endonyms (日本語 / EN) are passed in as
 * labels — intentionally non-translated (判例19).
 */
export function LangToggle({ items, activeId, onSelect, groupLabel }: LangToggleProps) {
  return (
    <span className="lang-toggle" title={groupLabel}>
      <IconGlobe />
      {items.map((item) => (
        <button
          key={item.id}
          type="button"
          className={activeId === item.id ? 'active' : ''}
          aria-pressed={activeId === item.id}
          onClick={() => {
            onSelect(item.id)
          }}
        >
          {item.label}
        </button>
      ))}
    </span>
  )
}
