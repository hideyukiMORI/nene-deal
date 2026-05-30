import { z } from 'zod'

const envSchema = z.object({
  VITE_API_BASE_URL: z.string().optional(),
  VITE_ORG_SLUG: z.string().optional(),
  VITE_API_KEY: z.string().optional(),
  VITE_REQUIRE_LOGIN: z.string().optional(),
})

const parsed = envSchema.parse({
  VITE_API_BASE_URL: import.meta.env['VITE_API_BASE_URL'] as string | undefined,
  VITE_ORG_SLUG: import.meta.env['VITE_ORG_SLUG'] as string | undefined,
  VITE_API_KEY: import.meta.env['VITE_API_KEY'] as string | undefined,
  VITE_REQUIRE_LOGIN: import.meta.env['VITE_REQUIRE_LOGIN'] as string | undefined,
})

export const env = {
  /** Empty string uses same-origin (Vite dev proxy → API). */
  apiBaseUrl: parsed.VITE_API_BASE_URL ?? '',
  /** Organization slug sent as `X-Organization-Slug`; empty for single-tenant. */
  orgSlug: parsed.VITE_ORG_SLUG ?? '',
  /** Machine API key sent as `X-NENE2-API-Key` on writes; empty when the API is open. */
  apiKey: parsed.VITE_API_KEY ?? '',
  /** Gate the app behind login. Set `true` when the backend enforces JWT auth. */
  requireLogin: parsed.VITE_REQUIRE_LOGIN === 'true',
} as const
