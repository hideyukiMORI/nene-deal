<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers authentication routes. `/auth/login` is public; `/auth/me`
 * requires a verified bearer token when JWT auth is enabled.
 */
final readonly class AuthRouteRegistrar
{
    public function __construct(
        private LoginHandler $loginHandler,
        private MeHandler $meHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $login = $this->loginHandler;
        $me = $this->meHandler;

        $router->post('/api/v1/auth/login', static fn (ServerRequestInterface $r): ResponseInterface => $login->handle($r));
        $router->get('/api/v1/auth/me', static fn (ServerRequestInterface $r): ResponseInterface => $me->handle($r));
    }
}
