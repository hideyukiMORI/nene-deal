import { http, HttpResponse } from 'msw'

// Organisation settings behind the /settings admin screen. Same reason as the
// user handler: without it the screen never leaves its loading state, so the
// visual smoke baseline for 7-settings would be timing-dependent (#169).
export const settingsHandlers = [
  http.get('/api/v1/settings', () => HttpResponse.json({ forecast_closing_day: 25 })),
]
