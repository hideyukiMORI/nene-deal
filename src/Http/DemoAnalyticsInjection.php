<?php

declare(strict_types=1);

namespace NeneDeal\Http;

/**
 * Env-gated, demo-only injection of a cookieless GoatCounter beacon into the
 * SPA shell `<head>`, with a matching Content-Security-Policy (#112).
 *
 * OSS-release integrity is the load-bearing constraint: the beacon is **never**
 * baked into the committed frontend build or the release ZIP. It is emitted
 * only by the PHP shell path in {@see \NeneDeal\Http\SpaShellFallback}, and only
 * when `DEMO_ANALYTICS_ENDPOINT` names a valid https origin — the disposable-demo
 * HETEML deployment sets it in its own `.env`, while the shipped `.env.example`
 * leaves it empty. A default (OSS) install therefore injects nothing and emits
 * no analytics CSP: the served shell is byte-identical to the built artifact, so
 * self-hosters ship zero telemetry.
 *
 * Self-hosted GoatCounter (`https://stats.ayane.co.jp`) is cookieless — no
 * consent gate, no PII. The single endpoint origin drives both the beacon URLs
 * (`/count.js`, `/count`) and the CSP allow-list; it is validated to a bare
 * https origin so a misconfigured value can neither break the head markup nor
 * silently widen the policy.
 */
final readonly class DemoAnalyticsInjection
{
    /**
     * Mirror of the default Content-Security-Policy in `public_html/.htaccess`
     * (the SPA shell policy — Google Fonts allowed, `script-src 'self'`). Only
     * consulted when demo analytics is enabled: this class then emits the CSP
     * itself (widened for the analytics origin) and `.htaccess` steps aside via
     * its `-z resp('Content-Security-Policy')` guard. Keep this string in
     * lockstep with the `.htaccess` `Header always set Content-Security-Policy`
     * value — a drift here only affects the demo shell.
     */
    private const string BASE_CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    private function __construct(private ?string $endpoint)
    {
    }

    /**
     * @param array<string, mixed> $env Environment map (typically `$_ENV`).
     */
    public static function fromEnv(array $env): self
    {
        $raw = is_string($env['DEMO_ANALYTICS_ENDPOINT'] ?? null) ? trim($env['DEMO_ANALYTICS_ENDPOINT']) : '';

        return new self(self::normaliseEndpoint($raw));
    }

    public function isEnabled(): bool
    {
        return $this->endpoint !== null;
    }

    /**
     * Returns the shell HTML with the beacon inserted just before `</head>`.
     * When disabled — or when the shell carries no `</head>` marker — the HTML
     * is returned byte-for-byte unchanged (OSS default: no injection).
     */
    public function injectHead(string $html): string
    {
        if ($this->endpoint === null) {
            return $html;
        }

        $pos = stripos($html, '</head>');
        if ($pos === false) {
            return $html;
        }

        return substr($html, 0, $pos) . '    ' . $this->beaconTag() . "\n  " . substr($html, $pos);
    }

    /**
     * The demo-only CSP for the shell: deal's production-proven `.htaccess`
     * BASE_CSP (identical React/Vite stack, so `style-src 'unsafe-inline'` and
     * the Google Fonts directives keep inline styles and web fonts working) with
     * the analytics origin added to exactly the three directives GoatCounter
     * needs — `count.js` (script-src), the hit beacon (`navigator.sendBeacon`
     * → connect-src; `<img>` pixel fallback → img-src). Every other directive is
     * left untouched. Returns null when disabled so the OSS shell keeps the
     * `.htaccess` default CSP unchanged.
     */
    public function contentSecurityPolicy(): ?string
    {
        if ($this->endpoint === null) {
            return null;
        }

        $origin = $this->endpoint;

        return str_replace(
            ["script-src 'self'", "img-src 'self' data:", "connect-src 'self'"],
            ["script-src 'self' {$origin}", "img-src 'self' data: {$origin}", "connect-src 'self' {$origin}"],
            self::BASE_CSP,
        );
    }

    /**
     * The cookieless GoatCounter beacon tag. Empty string when disabled. Both
     * URLs derive from the single validated origin; the origin is escaped for the
     * HTML-attribute context (belt-and-suspenders — it is already validated to a
     * bare origin).
     */
    public function beaconTag(): string
    {
        if ($this->endpoint === null) {
            return '';
        }

        $count = htmlspecialchars($this->endpoint . '/count', ENT_QUOTES);
        $countJs = htmlspecialchars($this->endpoint . '/count.js', ENT_QUOTES);

        return sprintf('<script data-goatcounter="%s" async src="%s"></script>', $count, $countJs);
    }

    /**
     * Accept only a bare https origin (scheme + host [+ optional port]). Anything
     * with a path, query, fragment, credentials, or unexpected characters is
     * rejected (→ null → disabled) so the value is safe in both an HTML-attribute
     * and an HTTP-header context. The value is operator-controlled (server
     * `.env`), but validating it keeps header/attribute construction provably safe.
     */
    private static function normaliseEndpoint(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        // Defence-in-depth for HTML-attribute + HTTP-header contexts.
        if (preg_match('/[\s"\'<>;]/', $raw) === 1) {
            return null;
        }

        $parts = parse_url($raw);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') === '') {
            return null;
        }

        foreach (['path', 'query', 'fragment', 'user', 'pass'] as $unexpected) {
            if (($parts[$unexpected] ?? '') !== '') {
                return null;
            }
        }

        $origin = 'https://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}
