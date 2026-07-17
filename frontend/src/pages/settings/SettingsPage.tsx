import { SettingsView, useSettingsPage } from '@/features/settings'

export function SettingsPage() {
  const page = useSettingsPage()
  return <SettingsView {...page} />
}
