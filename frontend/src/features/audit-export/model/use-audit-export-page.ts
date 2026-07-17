import { useState } from 'react'
import { downloadAuditCsv } from '../download-audit-csv'

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

function firstOfMonth(): string {
  return `${today().slice(0, 7)}-01`
}

export interface AuditExportPage {
  from: string
  to: string
  setFrom: (value: string) => void
  setTo: (value: string) => void
  invalidRange: boolean
  pending: boolean
  /** Downloads the CSV for the current range; resolves `true` on success. */
  download: () => Promise<boolean>
}

export function useAuditExportPage(): AuditExportPage {
  const [from, setFrom] = useState(firstOfMonth)
  const [to, setTo] = useState(today)
  const [pending, setPending] = useState(false)

  const invalidRange = from > to

  const download = async (): Promise<boolean> => {
    if (invalidRange || pending) return false
    setPending(true)
    try {
      await downloadAuditCsv(from, to)
      return true
    } catch {
      return false
    } finally {
      setPending(false)
    }
  }

  return { from, to, setFrom, setTo, invalidRange, pending, download }
}
