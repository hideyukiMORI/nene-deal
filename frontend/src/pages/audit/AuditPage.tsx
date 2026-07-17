import { AuditExportView, useAuditExportPage } from '@/features/audit-export'

export function AuditPage() {
  const page = useAuditExportPage()
  return <AuditExportView {...page} />
}
