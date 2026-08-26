import {
  expect,
  test,
  type Browser,
  type Page,
  type TestInfo,
} from "@playwright/test";
import { execSync } from "node:child_process";
import { mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Owner-review material for a kit-migration wave (#225).
 *
 * Produces what the owner looks at before the wave goes to production — full-page
 * screenshots of the same screens on PRODUCTION and on a LOCAL build, side by side in one
 * HTML file. A person opens `index.html`, looks, and records GO / NG **per screen** on the
 * wave's tracking issue. Nothing here decides anything.
 *
 * Written from nene-vault's `batch8-owner-review.spec.ts` per its README §"For another
 * ship": the two viewports and the two-column `index.html` are kept, `SCREENS` is deal's.
 *
 * 🔴 NOT A COMPARISON. The design-preservation constraint was lifted (ruling 2026-08-23);
 * the question the owner answers is "does it work and does it look put together", not "is it
 * identical". Content differs between the columns by design — each side mints its own
 * disposable org — so look at the chrome, not at the rows.
 *
 * 🔴 KNOW WHAT THE LEFT COLUMN IS. Production is whatever was last deployed, not "the
 * current design". For this wave it is `e361d3e` (2026-08-23 release), measured pre-kit:
 * `git grep nene2-ui e361d3e -- frontend/` returns nothing. `meta.json` records the local
 * side; the production build goes in the issue.
 *
 * NOT wired into CI, and it must not be: CI never touches production.
 *
 * Output: `docs/qa/owner-review/<name>/` — PNGs, `index.html`, `meta.json`. Gitignored: the
 * material is per-wave and disposable. What persists is the verdict on the issue and this
 * generator.
 */

const LOCAL_URL =
  process.env.NENE_DEAL_OWNER_REVIEW_LOCAL_URL ?? "http://localhost:5173";

/** Resolved from Playwright's `rootDir` (= tests/e2e/live), never from CWD. */
function outDir(rootDir: string, name: string): string {
  return resolve(rootDir, "../../../docs/qa/owner-review", name);
}

const VIEWPORTS = [
  { name: "desktop", width: 1280, height: 800 },
  { name: "mobile", width: 375, height: 812 },
] as const;

type ViewportName = (typeof VIEWPORTS)[number]["name"];

interface Screen {
  name: string;
  /**
   * Path to visit after seating.
   *
   * 🔴 Navigated by URL, not by clicking the nav. deal's shell is a desktop top nav plus a
   * **completely separate mobile UI** (`.m-topbar` / `.m-tabs`), so a click path would have
   * to branch per viewport and would break on the layout it is meant to photograph. The
   * routes are real (`app/routes`), so a URL reaches the same screen from either width.
   */
  path: string | ((page: Page) => Promise<string>);
  /** Extra steps after arriving (open a sheet, expand a row). */
  then?: (page: Page) => Promise<void>;
  /**
   * Viewport-only capture. A surface fixed to the viewport renders, in a full-page shot, as
   * a sliver at the top of a very tall column and shows nothing worth looking at.
   */
  viewportOnly?: boolean;
  /**
   * Only capture at these widths. deal's detail sheet is shown by CSS at ≤1024px only, so
   * asking for it on desktop would photograph the page behind it and quietly pass.
   */
  only?: ViewportName[];
}

const SCREENS: Screen[] = [
  { name: "board", path: "/" },
  {
    name: "deal-detail",
    // The board's deal ids are seeded per org, so read one instead of hard-coding it.
    path: async (page) => {
      await page.goto("/", { waitUntil: "networkidle" });
      // `data-deal`, not `data-deal-id` — and deleted cards deliberately carry none
      // (KanbanBoardView.tsx:356), so this picks a live deal without filtering.
      const card = page.locator("[data-deal]").first();
      await card.waitFor({ state: "visible", timeout: 15_000 });
      const id = await card.getAttribute("data-deal");
      if (id === null) throw new Error("no [data-deal] on the board");
      return `/deals/${id}`;
    },
  },
  { name: "stages", path: "/stages" },
  { name: "users", path: "/users" },
  { name: "audit", path: "/audit" },
  { name: "settings", path: "/settings" },
  {
    name: "user-sheet",
    path: "/users",
    viewportOnly: true,
    only: ["mobile"],
    then: async (page) => {
      await page.locator("button.user-id").first().click();
      // 🔴 `.m-sheet[role="dialog"]` alone matches two elements — the shell carries its own
      // account sheet with the same class and role. Only the open wrapper is unique
      // (`AppShell.tsx:246` adds `open` on demand; the users sheet is only rendered while
      // one is open), and it is structure rather than a translated aria-label.
      const sheet = page.locator('.m-sheet-wrap.open .m-sheet[role="dialog"]');
      await sheet.waitFor({ state: "visible", timeout: 10_000 });
      // A probe that silently matched two elements would still produce a picture — of
      // whichever one it happened to pick.
      expect(await sheet.count(), "user-sheet: selector is not unique").toBe(1);
    },
  },
];

interface Shot {
  side: "prod" | "local";
  screen: string;
  viewport: string;
  file: string | null;
  /** Why there is no picture — reported, never silently skipped. */
  note: string | null;
}

/**
 * 🔴 Every navigation must land seated, and this has to be *checked*, not assumed.
 *
 * The auth store is deliberately memory-only (`shared/auth/demo-seat.ts`: "reloading ends
 * the session"), so any full page load logs the visitor out and the app renders `/login`.
 * The first version of this harness navigated with `page.goto` and **photographed the login
 * page for all ten screens, then reported success** — every file written, no error raised.
 * The give-away was byte sizes: the mobile shots were all exactly 22,002 bytes.
 *
 * A screenshot harness cannot tell a screen from a login form. So assert the seat.
 */
async function assertSeated(page: Page, where: string): Promise<void> {
  const shell = await page.locator("header.topnav, header.m-topbar").count();
  expect(
    shell,
    `${where}: not seated — the app rendered the login screen`,
  ).toBeGreaterThan(0);
}

async function reach(page: Page, screen: Screen): Promise<void> {
  const path =
    typeof screen.path === "string" ? screen.path : await screen.path(page);
  await page.goto(path, { waitUntil: "networkidle" });
  await assertSeated(page, `${screen.name}`);
  if (screen.then) await screen.then(page);
  // Let transitions and lazy images settle; bounded, never fatal.
  await page.waitForTimeout(400);
}

/**
 * The unstyled-build guard (the `@source` regression — nene-vault #387, and the reason deal
 * carries `npm run source-probe`).
 *
 * 🔴 Checked on `/` (the board), not on a landing page: deal's own markup carries no kit
 * utility even when fully styled, so a guard there would pass on a build that generated none
 * of the kit's classes. The board is the first screen with kit components on it.
 */
async function assertStyled(page: Page, side: string): Promise<void> {
  const slots = await page.locator('[class*="x-slot"]').count();
  expect(
    slots,
    `${side}: no kit slot utility on the board — unstyled build?`,
  ).toBeGreaterThan(0);
}

async function shootSide(
  browser: Browser,
  side: "prod" | "local",
  base: string,
  dir: string,
  guardStyled: boolean,
): Promise<Shot[]> {
  const shots: Shot[] = [];
  const ctx = await browser.newContext({
    baseURL: base,
    viewport: { width: 1280, height: 800 },
    locale: "ja-JP",
  });
  const page = await ctx.newPage();

  // 🔴 Capture the bearer the seat mints, so every later navigation can re-park it.
  //
  // `/demo/standard` parks a token under `nene-deal-demo-seat`, and the SPA consumes it on
  // boot and **deletes the parked copy** (`demo-seat.ts`) — the store is memory-only on
  // purpose. So the token cannot be read out of storage afterwards; it is read off the wire
  // instead, from the `Authorization` header of the app's own API calls.
  let seat: string | null = null;
  page.on("request", (req) => {
    const auth = req.headers()["authorization"];
    if (seat === null && auth !== undefined && auth.startsWith("Bearer "))
      seat = auth.slice(7);
  });

  await page.goto("/demo/standard", { waitUntil: "networkidle" });
  await page
    .locator("header.topnav, header.m-topbar")
    .first()
    .waitFor({ timeout: 30_000 });

  if (seat === null) {
    throw new Error(
      `${side}: seated, but no Bearer went past — cannot re-seat later loads`,
    );
  }
  // Runs before page scripts on every navigation, so each full load finds a parked seat and
  // boots logged in. One org for the whole side; no re-minting (the demo rate-limits it).
  await page.addInitScript(
    ([key, token]) => {
      try {
        sessionStorage.setItem(key as string, token as string);
      } catch {
        /* storage blocked: the seat assertion will catch it */
      }
    },
    ["nene-deal-demo-seat", seat],
  );

  if (guardStyled) {
    await page.goto("/", { waitUntil: "networkidle" });
    await assertSeated(page, "style guard");
    await assertStyled(page, side);
  }

  for (const vp of VIEWPORTS) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    for (const screen of SCREENS) {
      if (screen.only && !screen.only.includes(vp.name)) {
        // Reported, not dropped: a blank cell would read as "we looked and it was fine".
        shots.push({
          side,
          screen: screen.name,
          viewport: vp.name,
          file: null,
          note: `not applicable at ${vp.name} (only: ${screen.only.join(", ")})`,
        });
        continue;
      }
      const file = `${side}-${screen.name}-${vp.name}.png`;
      try {
        await reach(page, screen);
        await page.screenshot({
          path: resolve(dir, file),
          fullPage: !screen.viewportOnly,
        });
        shots.push({
          side,
          screen: screen.name,
          viewport: vp.name,
          file,
          note: null,
        });
      } catch (e) {
        const note =
          (e as Error).message.split("\n")[0]?.slice(0, 200) ?? "unknown";
        console.log(
          `${side} ${screen.name} @${vp.name}: not captured — ${note}`,
        );
        shots.push({
          side,
          screen: screen.name,
          viewport: vp.name,
          file: null,
          note,
        });
      }
    }
  }
  await ctx.close();
  return shots;
}

function sh(cmd: string): string {
  try {
    return execSync(cmd, { encoding: "utf8" }).trim();
  } catch {
    return "unmeasured";
  }
}

function kitVersion(rootDir: string): string {
  try {
    const p = resolve(
      rootDir,
      "../../../frontend/node_modules/@hideyukimori/nene2-ui/package.json",
    );
    return (JSON.parse(readFileSync(p, "utf8")) as { version: string }).version;
  } catch {
    return "unmeasured";
  }
}

function esc(s: string): string {
  return s
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function renderIndex(meta: Record<string, string>, shots: Shot[]): string {
  const cell = (s: Shot | undefined): string => {
    if (!s) return '<td class="miss">not run</td>';
    if (!s.file)
      return `<td class="miss">not captured<br><small>${esc(s.note ?? "")}</small></td>`;
    return `<td><a href="${s.file}" target="_blank"><img src="${s.file}" alt="${esc(s.file)}" loading="lazy"></a></td>`;
  };
  const rows = VIEWPORTS.flatMap((vp) =>
    SCREENS.map((sc) => {
      const at = (side: "prod" | "local") =>
        shots.find(
          (s) =>
            s.side === side && s.screen === sc.name && s.viewport === vp.name,
        );
      return `<tr><th scope="row">${esc(sc.name)}<br><small>${vp.name} ${vp.width}×${vp.height}</small></th>${cell(at("prod"))}${cell(at("local"))}</tr>`;
    }),
  ).join("\n");
  const metaRows = Object.entries(meta)
    .map(
      ([k, v]) => `<tr><th scope="row">${esc(k)}</th><td>${esc(v)}</td></tr>`,
    )
    .join("\n");
  return `<!doctype html>
<meta charset="utf-8">
<title>Owner review — deal kit migration, ${esc(meta.date)}</title>
<style>
  body{font:14px/1.5 system-ui,sans-serif;margin:24px;color:#222;background:#fafafa}
  h1{font-size:20px;margin:0 0 4px}
  p.lede{margin:0 0 16px;color:#555;max-width:72ch}
  table{border-collapse:collapse;width:100%}
  th,td{border:1px solid #ddd;padding:8px;vertical-align:top;text-align:left}
  thead th{background:#eee;position:sticky;top:0}
  td img{max-width:100%;height:auto;display:block;border:1px solid #ccc;background:#fff}
  td.miss{color:#a00;background:#fff4f4}
  .meta{margin:0 0 20px;width:auto}
  .meta th{white-space:nowrap;background:#f3f3f3}
  small{color:#666}
</style>
<h1>Owner review — deal kit migration, ${esc(meta.date)}</h1>
<p class="lede">Left: production as served at run time. Right: the local build named below.
The question is "does it work, does it look put together" — not "is it identical".
Content differs between the columns because each side mints its own disposable demo org;
look at the chrome. Record GO / NG per screen on the wave's issue.</p>
<table class="meta"><tbody>${metaRows}</tbody></table>
<table>
<thead><tr><th>screen</th><th>production<br><small>${esc(meta.prod)}</small></th><th>local<br><small>${esc(meta.local)}</small></th></tr></thead>
<tbody>
${rows}
</tbody>
</table>
`;
}

test.describe.configure({ mode: "serial" });

test("OWNER-REVIEW: production vs local, every screen, two viewports", async ({
  browser,
}, testInfo: TestInfo) => {
  test.setTimeout(10 * 60_000);
  const rootDir = testInfo.config.rootDir;
  const date = new Date().toISOString().slice(0, 10);
  const dir = outDir(rootDir, process.env.NENE_DEAL_OWNER_REVIEW_DIR ?? date);
  mkdirSync(dir, { recursive: true });

  const prodBase = (
    testInfo.project.use.baseURL ?? "https://deal.ayane.co.jp"
  ).replace(/\/$/, "");
  const localBase = LOCAL_URL.replace(/\/$/, "");

  const meta: Record<string, string> = {
    date,
    "measured at (UTC)": new Date().toISOString(),
    prod: prodBase,
    local: localBase,
    "local HEAD": sh("git rev-parse --short HEAD"),
    "local branch": sh("git rev-parse --abbrev-ref HEAD"),
    // 🔴 vault #443: a bundle that names only its HEAD can be a build with uncommitted
    // changes in it. Say whether the tree was clean, in the material itself.
    "local worktree":
      sh("git status --porcelain") === "" ? "clean" : "🔴 DIRTY (uncommitted)",
    "local nene2-ui": kitVersion(rootDir),
  };

  // Local first: if the build is unstyled we learn it before minting a production org.
  const local = await shootSide(browser, "local", localBase, dir, true);
  const prod = await shootSide(browser, "prod", prodBase, dir, false);
  const shots = [...prod, ...local];

  writeFileSync(
    resolve(dir, "meta.json"),
    JSON.stringify({ meta, shots }, null, 2),
  );
  writeFileSync(resolve(dir, "index.html"), renderIndex(meta, shots));

  const captured = shots.filter((s) => s.file).length;
  console.log(
    `owner-review: ${captured}/${shots.length} captured → ${dir}/index.html`,
  );

  // The material is only useful if both sides produced something. Partial capture is
  // reported in the table, never hidden; nothing at all is a failed run.
  expect(
    local.some((s) => s.file),
    "no local screenshot at all",
  ).toBe(true);
  expect(
    prod.some((s) => s.file),
    "no production screenshot at all",
  ).toBe(true);
});
