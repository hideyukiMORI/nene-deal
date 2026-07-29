import { http, HttpResponse } from 'msw'

// Operator accounts behind the /users admin screen.
//
// Without this route the screen renders "Loading users…" forever, which is what
// the visual smoke would have captured as its baseline for 5-users — a state
// that depends on request timing rather than on the CSS under test. A 0px diff
// against a spinner proves nothing (#169 D family).
export const userHandlers = [
  http.get('/api/v1/users', () =>
    HttpResponse.json({
      data: [
        {
          id: '01USER0000000000000000000A',
          organization_id: '01ORG00000000000000000000A',
          email: 'operator@nene-deal.test',
          role: 'admin',
          status: 'active',
          created_at: '2026-05-01 09:00:00',
          updated_at: '2026-05-28 11:20:00',
        },
        {
          id: '01USER0000000000000000000B',
          organization_id: '01ORG00000000000000000000A',
          email: 'sato@nene-deal.test',
          role: 'operator',
          status: 'active',
          created_at: '2026-05-02 10:15:00',
          updated_at: '2026-05-27 16:40:00',
        },
        {
          id: '01USER0000000000000000000C',
          organization_id: '01ORG00000000000000000000A',
          email: 'takahashi@nene-deal.test',
          role: 'operator',
          status: 'disabled',
          created_at: '2026-05-03 14:05:00',
          updated_at: '2026-05-20 09:30:00',
        },
      ],
    }),
  ),
]
