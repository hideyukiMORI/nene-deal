<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Audit;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditTableConfig;
use Nene2\Audit\PdoAuditEventRepository;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use PHPUnit\Framework\TestCase;

/**
 * Pins the `audit_events` schema (migration mirror in deal_domain.sql) against
 * the canonical NENE2 {@see AuditTableConfig} used in production wiring — an
 * INSERT through {@see PdoAuditEventRepository} must succeed with ULID string
 * actor/organization ids (#89, ADR 0005).
 */
final class PdoAuditEventsSchemaTest extends TestCase
{
    private PdoDatabaseQueryExecutor $query;
    private PdoAuditEventRepository $repository;

    protected function setUp(): void
    {
        $config = new DatabaseConfig(
            url: null,
            environment: 'test',
            adapter: 'sqlite',
            host: 'localhost',
            port: 1,
            name: ':memory:',
            user: 'sqlite',
            password: '',
            charset: 'utf8',
        );
        $factory = new PdoConnectionFactory($config);
        $pdo = $factory->create();

        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema/deal_domain.sql');
        self::assertIsString($schema);
        $pdo->exec($schema);

        $this->query = new PdoDatabaseQueryExecutor($factory, $pdo);
        $this->repository = new PdoAuditEventRepository($this->query, AuditTableConfig::canonical());
    }

    public function test_appends_an_event_with_ulid_string_ids(): void
    {
        $this->repository->append(new AuditEvent(
            action: 'deal.created',
            entityType: 'deal',
            entityId: '01DEALAAAAAAAAAAAAAAAAAAAAA',
            actorId: '01USERADMIN000000000000001',
            organizationId: '01ORG00000000000000000000A',
            after: ['account_label' => '商談テスト', 'amount_cents' => 100000],
            occurredAt: '2026-07-11 12:00:00',
        ));

        $row = $this->query->fetchOne('SELECT * FROM audit_events WHERE entity_id = ?', ['01DEALAAAAAAAAAAAAAAAAAAAAA']);
        self::assertNotNull($row);
        self::assertSame('deal.created', $row['action']);
        self::assertSame('01USERADMIN000000000000001', $row['actor_id']);
        self::assertSame('01ORG00000000000000000000A', $row['organization_id']);
        self::assertIsString($row['after_json']);
        // Multibyte snapshots round-trip un-escaped (JSON_UNESCAPED_UNICODE).
        self::assertStringContainsString('商談テスト', $row['after_json']);
    }

    public function test_null_actor_and_org_are_stored_for_login_failures(): void
    {
        $this->repository->append(new AuditEvent(
            action: 'login_failed',
            entityType: 'user',
            after: ['email' => 'nobody@example.com', 'failure_reason' => 'invalid_credentials'],
            occurredAt: '2026-07-11 12:00:00',
        ));

        $row = $this->query->fetchOne('SELECT * FROM audit_events WHERE action = ?', ['login_failed']);
        self::assertNotNull($row);
        self::assertNull($row['actor_id']);
        self::assertNull($row['organization_id']);
    }
}
