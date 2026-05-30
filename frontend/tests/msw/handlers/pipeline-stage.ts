import { http, HttpResponse } from 'msw'

const ORG = '01ORG00000000000000000000A'

export const stageRecords = [
  {
    id: '01STAGELEAD0000000000000AA',
    slug: 'lead',
    label: 'Lead',
    sort_order: 1,
    is_terminal: false,
    is_won: false,
  },
  {
    id: '01STAGEQUAL0000000000000AA',
    slug: 'qualified',
    label: 'Qualified',
    sort_order: 2,
    is_terminal: false,
    is_won: false,
  },
  {
    id: '01STAGEPROP0000000000000AA',
    slug: 'proposal',
    label: 'Proposal',
    sort_order: 3,
    is_terminal: false,
    is_won: false,
  },
  {
    id: '01STAGEWON00000000000000AA',
    slug: 'won',
    label: 'Won',
    sort_order: 5,
    is_terminal: true,
    is_won: true,
  },
] as const

export const stageHandlers = [
  http.get('/api/v1/stages', () =>
    HttpResponse.json({
      data: stageRecords.map((stage) => ({ ...stage, organization_id: ORG })),
    }),
  ),
]
