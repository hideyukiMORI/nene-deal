<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use Nene2\Routing\Router;
use NeneDeal\Auth\RequireRoleMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the audit-export route. Admin only — the role middleware runs
 * inline via `process($r, $handler)`.
 */
final readonly class AuditRouteRegistrar
{
    public function __construct(
        private AuditCsvHandler $csvHandler,
        private RequireRoleMiddleware $requireAdmin,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $csv = $this->csvHandler;
        $admin = $this->requireAdmin;

        $router->get('/api/v1/audit/export', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $csv));
    }
}
