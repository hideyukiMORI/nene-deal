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
]
