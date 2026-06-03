<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use Nene2\Routing\Router;
use NeneDeal\Auth\RequireRoleMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers organization-settings routes. Read is open to any operator;
 * updates require the `admin` role (inline via `process`).
 */
final readonly class SettingsRouteRegistrar
{
    public function __construct(
        private GetSettingsHandler $getHandler,
        private UpdateSettingsHandler $updateHandler,
        private RequireRoleMiddleware $requireAdmin,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $get = $this->getHandler;
        $update = $this->updateHandler;
        $admin = $this->requireAdmin;

        $router->get('/api/v1/settings', static fn (ServerRequestInterface $r): ResponseInterface => $get->handle($r));
        $router->patch('/api/v1/settings', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $update));
    }
}
