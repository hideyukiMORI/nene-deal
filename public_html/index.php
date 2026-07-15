<?php

declare(strict_types=1);

use Nene2\Config\AppConfig;
use Nene2\Http\ResponseEmitter;
use NeneDeal\Http\DemoAnalyticsInjection;
use NeneDeal\Http\RuntimeContainerFactory;
use NeneDeal\Http\SpaShellFallback;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

// Strip the PHP version banner from every PHP-served response. The `.htaccess`
// `Header always unset X-Powered-By` only fires where mod_headers can act on the
// response, which does not cover PHP's `expose_php` header under every SAPI
// (verified leaking under mod_php). `header_remove()` is authoritative and
// portable across Docker and shared hosting (HETEML). Static assets are served
// by the web server and never carry this header.
header_remove('X-Powered-By');

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$psr17Factory = $container->get(Psr17Factory::class);
assert($psr17Factory instanceof Psr17Factory);

$serverRequestCreator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

$request = $serverRequestCreator->fromGlobals();

// Browser navigations receive the built SPA shell (public_html/index.html)
// through the front controller — not as a static Apache file — so the
// disposable-demo host can inject its env-gated, cookieless analytics beacon
// (#112) without ever baking it into the committed frontend build or the release
// ZIP. When DEMO_ANALYTICS_ENDPOINT is unset (every non-demo install) the shell
// is emitted byte-for-byte and no analytics CSP is set, so the .htaccess default
// CSP applies unchanged. API/infra/asset/demo routes fall through to the router.
$shellPath = SpaShellFallback::shellPath($request, __DIR__);

if ($shellPath !== null) {
    // Resolve AppConfig before reading $_ENV. The demo analytics endpoint lives
    // in the disposable-demo host's `.env`, and NENE2's ConfigLoader is what loads
    // `.env` into $_ENV (loadDotenvIfAvailable → Dotenv safeLoad). Without this the
    // shell branch never triggers config resolution, so on shared hosting (HETEML,
    // where the value is in `.env`) $_ENV['DEMO_ANALYTICS_ENDPOINT'] is absent and
    // the beacon silently never fires — while Docker/CI, which pass it as a process
    // env var, kept working. This mirrors the proven sibling NeNe Invoice
    // (public_html/index.php: `$container->get(AppConfig::class);` before it reads
    // env). A malformed `.env` must not take the public shell down, so a config
    // failure falls through to a disabled (no-beacon) injection, byte-identical to
    // the OSS default. AppConfig resolution is pure config/dotenv — no DB access.
    try {
        $container->get(AppConfig::class);
    } catch (\Throwable) {
        // Degraded: serve the shell without analytics (endpoint stays unset).
    }

    $analytics = DemoAnalyticsInjection::fromEnv($_ENV);
    $html = (string) file_get_contents($shellPath);

    $response = $psr17Factory->createResponse(200)
        ->withHeader('Content-Type', 'text/html; charset=UTF-8');

    $csp = $analytics->contentSecurityPolicy();
    if ($csp !== null) {
        $response = $response->withHeader('Content-Security-Policy', $csp);
    }

    $response->getBody()->write($analytics->injectHead($html));
} else {
    $application = $container->get(RequestHandlerInterface::class);
    assert($application instanceof RequestHandlerInterface);
    $response = $application->handle($request);
}

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);
