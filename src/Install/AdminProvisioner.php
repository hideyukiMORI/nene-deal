<?php

declare(strict_types=1);

namespace NeneDeal\Install;

use Nene2\Http\ClockInterface;
use PDO;
use RuntimeException;
use Symfony\Component\Uid\Ulid;
use Throwable;

/**
 * Provisions the first real admin account (and organization identity) right
 * after the installer applied the migrations.
 *
 * Deal's migrations seed a single organization (slug `default`) plus a
 * development operator with WELL-KNOWN credentials (`operator@nene-deal.test` /
 * `password`, promoted to admin for local dev). Leaving that account behind on a
 * production install would be a ready-made backdoor, so provisioning:
 *
 *   1. renames the seeded organization to the operator-chosen name,
 *   2. deletes the dev-seeded operator account,
 *   3. creates the admin account from the form input (password hashed with
 *      PASSWORD_DEFAULT, exactly like {@see \NeneDeal\User\CreateUserUseCase}).
 *
 * Everything runs in one transaction: a failure leaves the target in the
 * migrated-but-unprovisioned state, which {@see DatabaseProvisioningProbe}
 * deliberately treats as "not provisioned" so the install can be retried.
 * The plaintext password only ever lives in memory — it is never persisted.
 */
final readonly class AdminProvisioner
{
    /** Dev account seeded by migration 20260531130000_seed_default_operator. */
    public const string DEV_SEED_EMAIL = 'operator@nene-deal.test';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return array{organizationId: string, adminUserId: string, devSeedRemoved: bool}
     *
     * @throws RuntimeException when no organization exists (migrations did not run)
     *                          or the admin email is already taken.
     */
    public function provision(string $organizationName, string $adminEmail, string $adminPassword): array
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();

        try {
            $organizationId = $this->resolveOrganizationId();

            $update = $this->pdo->prepare('UPDATE organizations SET name = ?, updated_at = ? WHERE id = ?');
            $update->execute([$organizationName, $now, $organizationId]);

            $delete = $this->pdo->prepare('DELETE FROM users WHERE email = ?');
            $delete->execute([self::DEV_SEED_EMAIL]);
            $devSeedRemoved = $delete->rowCount() > 0;

            $taken = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $taken->execute([$adminEmail]);

            if ((int) $taken->fetchColumn() > 0) {
                throw new RuntimeException(sprintf('A user with the email %s already exists.', $adminEmail));
            }

            $adminUserId = (string) new Ulid();
            $insert = $this->pdo->prepare(
                'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
            );
            $insert->execute([
                $adminUserId,
                $organizationId,
                $adminEmail,
                password_hash($adminPassword, PASSWORD_DEFAULT),
                'admin',
                $now,
                $now,
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return [
            'organizationId' => $organizationId,
            'adminUserId' => $adminUserId,
            'devSeedRemoved' => $devSeedRemoved,
        ];
    }

    /**
     * The migration-seeded organization (slug `default`), falling back to the
     * sole/oldest organization so a renamed slug does not strand the installer.
     */
    private function resolveOrganizationId(): string
    {
        $bySlug = $this->pdo->prepare('SELECT id FROM organizations WHERE slug = ? LIMIT 1');
        $bySlug->execute(['default']);
        $id = $bySlug->fetchColumn();

        if (is_string($id) && $id !== '') {
            return $id;
        }

        $oldest = $this->pdo->query('SELECT id FROM organizations ORDER BY created_at ASC LIMIT 1');
        $id = $oldest === false ? false : $oldest->fetchColumn();

        if (is_string($id) && $id !== '') {
            return $id;
        }

        throw new RuntimeException('No organization found — the database migrations have not been applied.');
    }
}
