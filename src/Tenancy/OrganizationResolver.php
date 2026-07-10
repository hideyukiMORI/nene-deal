<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

/**
 * Looks up organization ids for tenant resolution. Returns ids (never entities)
 * so the resolver stays cheap and transport-agnostic.
 */
interface OrganizationResolver
{
    /** The sole organization id when exactly one is provisioned, else null. */
    public function soleId(): ?string;

    /** The organization id for a slug, or null when no such organization exists. */
    public function findIdBySlug(string $slug): ?string;

    /** Whether an organization with this id currently exists. */
    public function existsById(string $id): bool;
}
