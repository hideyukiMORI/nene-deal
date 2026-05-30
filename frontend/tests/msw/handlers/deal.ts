import { http, HttpResponse } from 'msw'

interface CreateDealBody {
  account_label?: string
  amount_cents?: number
  stage_id?: string
  probability_percent?: number
}

let nextId = 1

function dealResponse(id: string, body: CreateDealBody, stageSlug: string): unknown {
  const now = '2026-05-31 00:00:00'
  return {
    id,
    organization_id: '01ORG00000000000000000000A',
    account_label: body.account_label ?? '',
    amount_cents: body.amount_cents ?? 0,
    stage_id: body.stage_id ?? stageSlug,
    stage_slug: stageSlug,
    probability_percent: body.probability_percent ?? 0,
    created_at: now,
    updated_at: now,
  }
}

export const dealHandlers = [
  http.post('/api/v1/deals', async ({ request }) => {
    const body = (await request.json()) as CreateDealBody

    if (typeof body.account_label !== 'string' || body.account_label.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-deal.dev/problems/validation-failed',
          title: 'Validation Failed',
          status: 422,
          instance: '/api/v1/deals',
          detail: '"account_label" is required.',
        },
        { status: 422 },
      )
    }

    const id = `01DEALNEW${String(nextId++).padStart(17, '0')}`
    return HttpResponse.json(dealResponse(id, body, body.stage_id ?? 'lead'), { status: 201 })
  }),

  http.post('/api/v1/deals/:id/stage-change', async ({ params, request }) => {
    const body = (await request.json()) as { to_stage_id?: string }
    const id = typeof params.id === 'string' ? params.id : 'unknown'
    return HttpResponse.json(
      dealResponse(
        id,
        { account_label: 'Moved deal', amount_cents: 1000 },
        body.to_stage_id ?? 'lead',
      ),
    )
  }),

  http.get('/api/v1/deals/:id', ({ params }) => {
    const id = typeof params.id === 'string' ? params.id : 'unknown'
    return HttpResponse.json(
      dealResponse(
        id,
        { account_label: 'Acme Corp', amount_cents: 150_000_000, probability_percent: 100 },
        'won',
      ),
    )
  }),

  http.patch('/api/v1/deals/:id', async ({ params, request }) => {
    const body = (await request.json()) as CreateDealBody
    const id = typeof params.id === 'string' ? params.id : 'unknown'

    if (typeof body.account_label !== 'string' || body.account_label.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-deal.dev/problems/validation-failed',
          title: 'Validation Failed',
          status: 422,
          instance: `/api/v1/deals/${id}`,
          detail: '"account_label" must be a non-empty string.',
        },
        { status: 422 },
      )
    }

    return HttpResponse.json(dealResponse(id, body, 'won'))
  }),

  http.post('/api/v1/deals/:id/invoice-handoff', ({ params }) => {
    const id = typeof params.id === 'string' ? params.id : 'unknown'
    return HttpResponse.json({
      deal_id: id,
      invoice_client_id: 4821,
      invoice_quote_id: 9930,
      handoff_at: '2026-05-31 00:00:00',
      handoff_actor_user_id: null,
    })
  }),
]
