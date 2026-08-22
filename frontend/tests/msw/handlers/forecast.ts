import { http, HttpResponse } from 'msw'

export const forecastHandlers = [
  http.get('/api/v1/forecast', ({ request }) => {
    const month = new URL(request.url).searchParams.get('month') ?? '2026-06'
    return HttpResponse.json({
      month,
      open_deal_count: 1,
      pipeline_total_cents: 1_500_000,
      weighted_total_cents: 600_000,
      by_stage: [
        {
          stage_id: '01STAGELEAD0000000000000AA',
          slug: 'lead',
          deal_count: 1,
          total_cents: 1_500_000,
          weighted_total_cents: 600_000,
        },
      ],
    })
  }),
]
