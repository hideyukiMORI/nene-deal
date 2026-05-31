<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Routing\Router;
use NeneDeal\Auth\RequireRoleMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers user management routes. All routes require the `admin` role;
 * the role middleware runs inline via `process($r, $handler)`.
 */
final readonly class UserRouteRegistrar
{
    public function __construct(
        private ListUsersHandler $listHandler,
        private CreateUserHandler $createHandler,
        private GetUserHandler $getHandler,
        private UpdateUserHandler $updateHandler,
        private DeleteUserHandler $deleteHandler,
        private RequireRoleMiddleware $requireAdmin,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $create = $this->createHandler;
        $get = $this->getHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $admin = $this->requireAdmin;

        $router->get('/api/v1/users', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $list));
        $router->post('/api/v1/users', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $create));
        $router->get('/api/v1/users/{userId}', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $get));
        $router->patch('/api/v1/users/{userId}', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $update));
        $router->delete('/api/v1/users/{userId}', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $delete));
    }
}
