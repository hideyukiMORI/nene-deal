import { expect, test, type Route } from '@playwright/test'

// Minimal @smoke — fleet T2 pilot (Issue #157).
//
// Critical path: boot the SPA → sign in → land on the authenticated board
// shell. The API is stubbed (page.route) so the run is hermetic — no backend,
// DB, or Docker. This asserts the *frontend wiring* of the login flow
// (form → token store → fail-closed AuthGate → shell render), not the real
// backend auth, which is covered by backend + unit tests.
//
// Contracts mirrored from docs/openapi/openapi.yaml:
//   POST /api/v1/auth/login -> { token }
//   GET  /api/v1/auth/me    -> { id, email, role, organization_id }
//   GET  /api/v1/board      -> { columns: [] }
//   GET  /api/v1/{stages,forecast,...} -> permissive empties

const json = (route: Route, body: unknown): Promise<void> =>
  route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify(body),
  })

test('@smoke sign in and reach the board shell', async ({ page }) => {
  await page.route('**/api/v1/**', async (route) => {
    const url = route.request().url()
    const method = route.request().method()

    if (url.includes('/api/v1/auth/login') && method === 'POST') {
      await json(route, { token: 'smoke-token' })
      return
    }
    if (url.includes('/api/v1/auth/me')) {
      await json(route, {
        id: 'usr_smoke',
        email: 'smoke@example.com',
        role: 'admin',
        organization_id: 'org_smoke',
      })
      return
    }
    if (url.includes('/api/v1/board')) {
      await json(route, { columns: [] })
      return
    }
    if (url.includes('/api/v1/forecast')) {
      await json(route, { total_amount_cents: 0, entries: [] })
      return
    }
    // Stage list and any other authed-shell fetch: permissive empty array.
    await json(route, [])
  })

  // Boot → login page.
  await page.goto('/login')
  await expect(page.locator('#login-email')).toBeVisible()

  // Sign in.
  await page.locator('#login-email').fill('smoke@example.com')
  await page.locator('#login-password').fill('correct horse battery staple')
  await page.locator('button[type="submit"]').click()

  // Land on the authenticated board shell (root path, not /login).
  await expect(page).toHaveURL(/\/$/)
  // The authed shell renders its top nav (proves AuthGate opened, not just a token).
  await expect(page.getByRole('navigation').first()).toBeVisible()
  await expect(page.locator('.topnav-links .topnav-link').first()).toBeVisible()
})
