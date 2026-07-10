<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use LogicException;

/**
 * In-process registry translating Deal's ULID identifiers to the `int` handles
 * the `Nene2\Demo` module passes around.
 *
 * The framework contracts ({@see \Nene2\Demo\ProvisionedDemoOrg},
 * {@see \Nene2\Demo\DisposableOrgReaperInterface},
 * {@see \Nene2\Demo\DemoDataSeederInterface}) type organization and admin ids
 * as `int`, while Deal keys both `organizations` and `users` by 26-char ULID
 * strings. Rather than forking the framework, the ids only ever cross the
 * module boundary as opaque handles minted here and resolved back by the
 * product concretes on the other side.
 *
 * This works because every framework flow that carries a handle is confined to
 * one PHP process sharing one registry instance:
 *
 * - the start route: provision → seed → seat inside a single request, and
 * - the sweep: `tools/sweep-demo.php` registers each candidate org itself
 *   before handing the records to the framework sweeper, which calls straight
 *   back into {@see DemoOrgReaper}.
 *
 * Handles are meaningless outside the process that minted them and must never
 * be persisted.
 */
final class DemoOrgHandles
{
    /** @var array<int, array{org: string, admin: ?string, slug: ?string}> */
    private array $entries = [];

    private int $next = 1;

    /** Registers one demo org and returns its process-local handle. */
    public function register(string $organizationId, ?string $adminUserId = null, ?string $slug = null): int
    {
        $handle = $this->next++;
        $this->entries[$handle] = ['org' => $organizationId, 'admin' => $adminUserId, 'slug' => $slug];

        return $handle;
    }

    public function organizationId(int $handle): string
    {
        return $this->entry($handle)['org'];
    }

    public function adminUserId(int $handle): string
    {
        $admin = $this->entry($handle)['admin'];

        if ($admin === null) {
            throw new LogicException(sprintf('Demo org handle #%d was registered without an admin user id.', $handle));
        }

        return $admin;
    }

    public function slug(int $handle): string
    {
        $slug = $this->entry($handle)['slug'];

        if ($slug === null) {
            throw new LogicException(sprintf('Demo org handle #%d was registered without a slug.', $handle));
        }

        return $slug;
    }

    /** @return array{org: string, admin: ?string, slug: ?string} */
    private function entry(int $handle): array
    {
        if (!isset($this->entries[$handle])) {
            throw new LogicException(sprintf('Unknown demo org handle #%d — handles are process-local and must come from this registry.', $handle));
        }

        return $this->entries[$handle];
    }
}
