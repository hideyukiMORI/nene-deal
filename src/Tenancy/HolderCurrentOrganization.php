<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

use Nene2\Http\RequestScopedHolder;

/**
 * {@see CurrentOrganization} backed by the request-scoped holder populated by
 * {@see RequestOrganizationMiddleware}. Reading before the middleware ran (or
 * when no organization resolved) throws — repositories fail closed rather than
 * silently crossing tenants.
 */
final readonly class HolderCurrentOrganization implements CurrentOrganization
{
    /**
     * @param RequestScopedHolder<string> $orgIdHolder
     */
    public function __construct(
        private RequestScopedHolder $orgIdHolder,
    ) {
    }

    public function id(): string
    {
        return $this->orgIdHolder->get();
    }
}
