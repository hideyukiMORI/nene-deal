import { z } from 'zod'

const envSchema = z.object({
  VITE_API_BASE_URL: z.string().optional(),
})

const parsed = envSchema.parse({
  VITE_API_BASE_URL: import.meta.env['VITE_API_BASE_URL'] as string | undefined,
})

export const env = {
  /** Empty string uses same-origin (Vite dev proxy → API). */
  apiBaseUrl: parsed.VITE_API_BASE_URL ?? '',
} as const
