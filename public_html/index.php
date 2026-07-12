<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeneDeal\Http\AuthorizationHeaderFallback;
use NeneDeal\Http\DemoAnalyticsInjection;
use NeneDeal\Http\RuntimeContainerFactory;
use NeneDeal\Http\SpaShellFallback;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$psr17Factory = $container->get(Psr17Factory::class);
assert($psr17Factory instanceof Psr17Factory);

$serverRequestCreator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

$request = AuthorizationHeaderFallback::apply($serverRequestCreator->fromGlobals());

// Browser navigations receive the built SPA shell (public_html/index.html)
// through the front controller — not as a static Apache file — so the
// disposable-demo host can inject its env-gated, cookieless analytics beacon
// (#112) without ever baking it into the committed frontend build or the release
// ZIP. When DEMO_ANALYTICS_ENDPOINT is unset (every non-demo install) the shell
// is emitted byte-for-byte and no analytics CSP is set, so the .htaccess default
// CSP applies unchanged. API/infra/asset/demo routes fall through to the router.
$shellPath = SpaShellFallback::shellPath($request, __DIR__);

if ($shellPath !== null) {
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
