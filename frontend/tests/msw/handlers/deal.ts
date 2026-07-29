import { http, HttpResponse } from 'msw'

interface CreateDealBody {
  account_label?: string
  amount_cents?: number
  stage_id?: string
  probability_percent?: number
}

let nextId = 1

function dealResponse(
  id: string,
  body: CreateDealBody,
  stageSlug: string,
): Record<string, unknown> {
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

  // Stage/field history behind the deal-detail activity timeline.
  //
  // This route was missing, so `useDealActivity` always resolved to [] and the
  // timeline rendered its empty state — including in the visual smoke, whose
  // `3-deal` shot therefore never covered the timeline at all. Added while
  // draining the C family (#169) so that drain has something to verify against.
  http.get('/api/v1/deals/:id/history', ({ params }) => {
    const id = typeof params.id === 'string' ? params.id : 'unknown'
    return HttpResponse.json({
      data: [
        {
          id: '01HIST00000000000000000003',
          deal_id: id,
          action: 'stage_changed',
          from_stage_id: 'proposal',
          to_stage_id: 'won',
          actor_user_id: '01USER0000000000000000000A',
          actor_label: 'operator@nene-deal.test',
          changes: null,
          created_at: '2026-05-31 09:15:00',
        },
        {
          id: '01HIST00000000000000000002',
          deal_id: id,
          action: 'updated',
          from_stage_id: null,
          to_stage_id: null,
          actor_user_id: '01USER0000000000000000000A',
          actor_label: 'operator@nene-deal.test',
          changes: {
            amount_cents: { from: 120_000_000, to: 150_000_000 },
            probability_percent: { from: 60, to: 100 },
          },
          created_at: '2026-05-30 14:02:00',
        },
        {
          id: '01HIST00000000000000000001',
          deal_id: id,
          action: 'created',
          from_stage_id: null,
          to_stage_id: 'lead',
          actor_user_id: '01USER0000000000000000000A',
          actor_label: 'operator@nene-deal.test',
          changes: null,
          created_at: '2026-05-28 10:00:00',
        },
      ],
    })
  }),
]
