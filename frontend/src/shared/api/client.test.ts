import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { http, HttpResponse } from 'msw'
import { mswServer } from '@tests/msw/server'
import { authStore } from '@/shared/auth'
import { apiClient, AppError } from './client'

const TOKEN = 'header.payload.signature'

describe('apiClient (nene2-client transport adapter)', () => {
  beforeEach(() => {
    authStore.setToken(TOKEN)
  })
  afterEach(() => {
    authStore.clear()
  })

  it('mirrors the bearer token onto both Authorization and X-Authorization on GET (#83)', async () => {
    let authorization: string | null = null
    let xAuthorization: string | null = null

    mswServer.use(
      http.get('/api/v1/__transport-test/widgets', ({ request }) => {
        authorization = request.headers.get('Authorization')
        xAuthorization = request.headers.get('X-Authorization')
        return HttpResponse.json({ ok: true })
      }),
    )

    await apiClient.get('/api/v1/__transport-test/widgets')

    expect(authorization).toBe(`Bearer ${TOKEN}`)
    expect(xAuthorization).toBe(`Bearer ${TOKEN}`)
  })

  it('mirrors both headers on POST/PATCH/DELETE as well', async () => {
    const seen: Record<string, { auth: string | null; xAuth: string | null }> = {}

    mswServer.use(
      http.post('/api/v1/__transport-test/widgets', ({ request }) => {
        seen['post'] = {
          auth: request.headers.get('Authorization'),
          xAuth: request.headers.get('X-Authorization'),
        }
        return HttpResponse.json({ ok: true })
      }),
      http.patch('/api/v1/__transport-test/widgets/1', ({ request }) => {
        seen['patch'] = {
          auth: request.headers.get('Authorization'),
          xAuth: request.headers.get('X-Authorization'),
        }
        return HttpResponse.json({ ok: true })
      }),
      http.delete('/api/v1/__transport-test/widgets/1', ({ request }) => {
        seen['delete'] = {
          auth: request.headers.get('Authorization'),
          xAuth: request.headers.get('X-Authorization'),
        }
        return new HttpResponse(null, { status: 204 })
      }),
    )

    await apiClient.post('/api/v1/__transport-test/widgets', { name: 'x' })
    await apiClient.patch('/api/v1/__transport-test/widgets/1', { name: 'y' })
    await apiClient.delete('/api/v1/__transport-test/widgets/1')

    for (const method of ['post', 'patch', 'delete']) {
      expect(seen[method]?.auth, `${method} Authorization`).toBe(`Bearer ${TOKEN}`)
      expect(seen[method]?.xAuth, `${method} X-Authorization`).toBe(`Bearer ${TOKEN}`)
    }
  })

  it('sends no auth headers when signed out', async () => {
    authStore.clear()
    let authorization: string | null = null

    mswServer.use(
      http.get('/api/v1/__transport-test/widgets', ({ request }) => {
        authorization = request.headers.get('Authorization')
        return HttpResponse.json({ ok: true })
      }),
    )

    await apiClient.get('/api/v1/__transport-test/widgets')

    expect(authorization).toBeNull()
  })

  it('clears the in-memory auth store on a 401 to an authenticated request', async () => {
    mswServer.use(
      http.get('/api/v1/__transport-test/widgets', () => new HttpResponse(null, { status: 401 })),
    )

    await expect(apiClient.get('/api/v1/__transport-test/widgets')).rejects.toBeInstanceOf(AppError)

    expect(authStore.getToken()).toBeNull()
  })

  it('maps a Problem Details error response to AppError (unchanged public shape)', async () => {
    mswServer.use(
      http.get('/api/v1/__transport-test/widgets', () =>
        HttpResponse.json(
          {
            type: 'https://nene-deal.dev/problems/internal-server-error',
            title: 'Server Error',
            status: 500,
            instance: '/api/v1/__transport-test/widgets',
          },
          { status: 500 },
        ),
      ),
    )

    await expect(apiClient.get('/api/v1/__transport-test/widgets')).rejects.toMatchObject({
      status: 500,
      title: 'Server Error',
      type: 'https://nene-deal.dev/problems/internal-server-error',
      instance: '/api/v1/__transport-test/widgets',
    })
    await expect(apiClient.get('/api/v1/__transport-test/widgets')).rejects.toBeInstanceOf(AppError)
  })

  it('carries field-level validation errors through to AppError.errors', async () => {
    mswServer.use(
      http.post('/api/v1/__transport-test/widgets', () =>
        HttpResponse.json(
          {
            type: 'https://nene-deal.dev/problems/validation-failed',
            title: 'Validation Failed',
            status: 422,
            instance: '/api/v1/__transport-test/widgets',
            errors: [{ field: 'account_label', message: 'Required', code: 'required' }],
          },
          { status: 422 },
        ),
      ),
    )

    await expect(apiClient.post('/api/v1/__transport-test/widgets', {})).rejects.toMatchObject({
      status: 422,
      errors: [{ field: 'account_label', message: 'Required', code: 'required' }],
    })
  })
})
