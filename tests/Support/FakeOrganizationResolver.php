<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Tenancy\OrganizationResolver;

/**
 * In-memory {@see OrganizationResolver} for middleware tests.
 */
final class FakeOrganizationResolver implements OrganizationResolver
{
    /**
     * @param array<string, string> $bySlug slug => id
     * @param list<string> $existingIds ids {@see existsById()} affirms; ids
     *        known via $bySlug or $soleId exist implicitly
     */
    public function __construct(
        private readonly array $bySlug = [],
        private readonly ?string $soleId = null,
        private readonly array $existingIds = [],
    ) {
    }

    public function soleId(): ?string
    {
        return $this->soleId;
    }

    public function findIdBySlug(string $slug): ?string
    {
        return $this->bySlug[$slug] ?? null;
    }

    public function existsById(string $id): bool
    {
        return in_array($id, $this->existingIds, true)
            || in_array($id, $this->bySlug, true)
            || $id === $this->soleId;
    }
}
