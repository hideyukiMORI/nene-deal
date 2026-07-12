<?php

declare(strict_types=1);

namespace NeneDeal\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Decides whether a request is a browser navigation that should receive the
 * built SPA shell (`public_html/index.html`) through the front controller
 * instead of hitting the JSON API router (#112).
 *
 * Deal's release layers the Vite build's `index.html` into the document root
 * ({@see tools/build-release.sh}). Serving that shell *through PHP* — rather than
 * letting Apache return it as a static file — is what lets the disposable-demo
 * host inject its env-gated cookieless analytics beacon
 * ({@see \NeneDeal\Http\DemoAnalyticsInjection}) without ever baking it into the
 * committed frontend build. On every non-demo install the shell is emitted
 * byte-for-byte and no analytics CSP is set.
 *
 * Browsers send `Accept: text/html,…`; API clients send `application/json` or a
 * bare wildcard, so the shell is served only when `text/html` is explicit (and
 * `application/json` is not). The API/infra surfaces (`/api/`, `/health`,
 * `/machine/`), static assets (`/assets/`), and the demo start route (which
 * serves its own nonce'd seat page, {@see \NeneDeal\Demo\DemoSessionSeater})
 * are never intercepted.
 */
final class SpaShellFallback
{
    /**
     * Returns the shell path to serve, or null when the request must reach the
     * application (API/infra/asset/demo routes, non-GET, non-HTML, or when the
     * SPA has not been built into the document root — e.g. dev, where Vite
     * serves the frontend and no `index.html` exists here).
     */
    public static function shellPath(ServerRequestInterface $request, string $publicHtmlDir): ?string
    {
        if ($request->getMethod() !== 'GET') {
            return null;
        }

        $accept = $request->getHeaderLine('Accept');
        $wantsHtml = str_contains($accept, 'text/html') && !str_contains($accept, 'application/json');
        if (!$wantsHtml) {
            return null;
        }

        $path = $request->getUri()->getPath() ?: '/';
        foreach (['/api/', '/health', '/machine/', '/assets/', '/demo/'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) {
                return null;
            }
        }

        $shell = $publicHtmlDir . '/index.html';

        return is_file($shell) ? $shell : null;
    }
}
