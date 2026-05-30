<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

/**
 * Resolves the organization id that scopes the current request.
 *
 * MVP is single-organization: the implementation returns the sole seeded
 * organization. When multi-tenant resolution lands (a later issue), this
 * becomes request-scoped (resolved from subdomain / path / token) without
 * changing repository code — every repository already scopes by this id.
 */
interface CurrentOrganization
{
    public function id(): string;
}
