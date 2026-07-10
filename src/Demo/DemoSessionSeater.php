<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Demo\DemoSessionSeaterInterface;
use Nene2\Demo\ProvisionedDemoOrg;
use Nene2\Http\ClockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Seats the visitor into a freshly provisioned demo org (`Nene2\Demo`
 * consumer, #69) — the Deal-specific JWT design the framework module left to
 * the product, transplanted from Clear's #275 bearer seater.
 *
 * Deal's SPA keeps its bearer token in JS memory only (deliberately not
 * persisted — see `frontend/src/shared/auth/auth-store.ts`) and sends it on
 * every API call; there is no cookie session to set, so the invoice cookie
 * seater does not transplant. Instead the seater mints a normal access token
 * for the demo admin — same claims shape, TTL and secret as
 * {@see \NeneDeal\Auth\LoginUseCase} — and serves a one-shot **seat page**: a
 * nonce'd inline script parks the token in `sessionStorage` under a well-known
 * key and `location.replace('/')` into the SPA, whose boot imports the token
 * into the in-memory store and immediately removes the parked copy
 * (`frontend/src/shared/auth/demo-seat.ts`). Auth code is untouched.
 *
 * The page carries its own per-response CSP — `script-src 'nonce-…'` with
 * everything else locked — because the app-wide `default-src 'self'` would
 * block the inline script (the invoice #612 trap; the NENE2 security-headers
 * middleware only fills headers that are absent, so this page-specific policy
 * wins). The script embeds only server-generated values (a JSON-encoded JWT);
 * no request input is echoed.
 *
 * Product-honest constraints carried into the session: the token expires
 * after the normal TTL (1 h) with no refresh, and a reload drops the
 * in-memory copy — either way the demo simply asks for a fresh
 * `/demo/{template}` visit, which provisions a brand-new org.
 */
final readonly class DemoSessionSeater implements DemoSessionSeaterInterface
{
    /** Same TTL as {@see \NeneDeal\Auth\LoginUseCase}. */
    private const int TOKEN_TTL_SECONDS = 3600;

    /** Must match `SEAT_STORAGE_KEY` in `frontend/src/shared/auth/demo-seat.ts`. */
    private const string SEAT_STORAGE_KEY = 'nene-deal-demo-seat';

    public function __construct(
        private TokenIssuerInterface $tokenIssuer,
        private ClockInterface $clock,
        private DemoOrgHandles $handles,
        private Psr17Factory $psr17,
    ) {
    }

    public function seatAndRedirect(ServerRequestInterface $request, ProvisionedDemoOrg $org): ResponseInterface
    {
        $now = $this->clock->now()->getTimestamp();

        // Same claims shape as a real login (sub / role / org / iat / exp) so
        // the bearer middleware, AuthContext and the claim-based tenant
        // resolution treat a seated demo session exactly like a logged-in one.
        $token = $this->tokenIssuer->issue([
            'sub' => $this->handles->adminUserId($org->adminUserId),
            'role' => 'admin',
            'org' => $this->handles->organizationId($org->orgId),
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL_SECONDS,
        ]);

        $nonce = base64_encode(random_bytes(18));
        $seatKey = self::SEAT_STORAGE_KEY;
        $tokenJs = json_encode($token, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="ja">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>NeNe Deal — デモを準備しています…</title>
        </head>
        <body>
        <noscript><p>デモの開始には JavaScript が必要です。ブラウザの JavaScript を有効にして、もう一度お試しください。</p></noscript>
        <p>デモを準備しています…</p>
        <script nonce="{$nonce}">
        sessionStorage.setItem('{$seatKey}', {$tokenJs});
        location.replace('/');
        </script>
        </body>
        </html>
        HTML;

        $response = $this->psr17->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            // Page-specific CSP: allow exactly this one nonce'd script; lock the rest.
            ->withHeader('Content-Security-Policy', "default-src 'none'; script-src 'nonce-{$nonce}'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'")
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Referrer-Policy', 'no-referrer');
        $response->getBody()->write($html);

        return $response;
    }
}
