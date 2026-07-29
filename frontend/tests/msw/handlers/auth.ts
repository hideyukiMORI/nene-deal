import { http, HttpResponse } from 'msw'

export const authHandlers = [
  http.post('/api/v1/auth/login', async ({ request }) => {
    const body = (await request.json()) as { email?: string; password?: string }

    if (body.email === 'operator@nene-deal.test' && body.password === 'password') {
      return HttpResponse.json({ token: 'header.payload.signature' })
    }

    return HttpResponse.json(
      {
        type: 'https://nene-deal.dev/problems/invalid-credentials',
        title: 'Invalid Credentials',
        status: 401,
        instance: '/api/v1/auth/login',
        detail: 'Invalid email or password.',
      },
      { status: 401 },
    )
  }),

  // Current user behind `useCurrentUser`. Without this route the query fails,
  // AppShell falls back to non-admin, and the admin nav (Stages / Users /
  // Audit / Settings) never renders — so those routes were unreachable in the
  // mock app AND never captured by the visual smoke. Added while draining the
  // D family (#169); the role is `admin` so the full nav is exercised.
  http.get('/api/v1/auth/me', () => {
    return HttpResponse.json({
      id: '01USER0000000000000000000A',
      organization_id: '01ORG00000000000000000000A',
      email: 'operator@nene-deal.test',
      role: 'admin',
      status: 'active',
    })
  }),
]
