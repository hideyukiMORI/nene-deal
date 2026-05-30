import type { SupportedLocale } from '../locales'
import type { MessageCatalog } from './ja'
import { ja } from './ja'
import { en } from './en'

const MESSAGES: Record<SupportedLocale, MessageCatalog> = { ja, en }

export function getMessages(locale: SupportedLocale): MessageCatalog {
  return MESSAGES[locale]
}
