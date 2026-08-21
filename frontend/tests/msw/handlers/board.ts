import { http, HttpResponse } from 'msw'

export const boardHandlers = [
  http.get('/api/v1/board', () =>
    HttpResponse.json({
      columns: [
        {
          stage: { id: '01STAGELEAD0000000000000AA', slug: 'lead', label: 'Lead', is_won: false },
          deals: [
            {
              id: '01DEALACME000000000000000A',
              account_label: 'Acme Corp',
              amount_cents: 1_500_000,
              stage_slug: 'lead',
              probability_percent: 40,
            },
          ],
          deal_count: 1,
          total_cents: 1_500_000,
          weighted_total_cents: 600_000,
        },
        {
          stage: {
            id: '01STAGEPROP0000000000000AA',
            slug: 'proposal',
            label: 'Proposal',
            is_won: false,
          },
          deals: [],
          deal_count: 0,
          total_cents: 0,
          weighted_total_cents: 0,
        },
      ],
    }),
  ),
]
