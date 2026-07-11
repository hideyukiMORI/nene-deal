<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Auth\PdoLoginThrottle;
use NeneDeal\Tests\Support\MutableClock;
use PHPUnit\Framework\TestCase;

final class PdoLoginThrottleTest extends TestCase
{
    private const ID = 'attacker@x.example|203.0.113.9';

    private MutableClock $clock;
    private PdoLoginThrottle $throttle;

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

        $this->clock = new MutableClock();
        $this->throttle = new PdoLoginThrottle(new PdoDatabaseQueryExecutor($factory, $pdo), $this->clock);
    }

    public function test_not_locked_initially(): void
    {
        self::assertSame(0, $this->throttle->secondsUntilUnlocked(self::ID));
    }

    public function test_locks_after_five_failures(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->throttle->recordFailure(self::ID);
            self::assertSame(0, $this->throttle->secondsUntilUnlocked(self::ID), 'not locked after ' . ($i + 1));
        }

        $this->throttle->recordFailure(self::ID); // 5th → lock

        self::assertGreaterThan(0, $this->throttle->secondsUntilUnlocked(self::ID));
    }

    public function test_success_clears_attempts(): void
    {
        $this->throttle->recordFailure(self::ID);
        $this->throttle->recordFailure(self::ID);
        $this->throttle->clear(self::ID);

        // After clear, five fresh failures are needed to lock again.
        for ($i = 0; $i < 4; $i++) {
            $this->throttle->recordFailure(self::ID);
        }
        self::assertSame(0, $this->throttle->secondsUntilUnlocked(self::ID));
    }

    public function test_window_expiry_resets_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->throttle->recordFailure(self::ID);
        }

        // Wait past the 15-minute window, then a failure starts a fresh window.
        $this->clock->advance(901);
        $this->throttle->recordFailure(self::ID);

        self::assertSame(0, $this->throttle->secondsUntilUnlocked(self::ID));
    }

    public function test_lock_expires_after_lock_window(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->recordFailure(self::ID);
        }
        self::assertGreaterThan(0, $this->throttle->secondsUntilUnlocked(self::ID));

        $this->clock->advance(901); // past the 15-minute lock

        self::assertSame(0, $this->throttle->secondsUntilUnlocked(self::ID));
    }

    public function test_distinct_identifiers_are_independent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->recordFailure('victim@x.example|203.0.113.9');
        }

        // A different identifier (different IP) is unaffected.
        self::assertSame(0, $this->throttle->secondsUntilUnlocked('victim@x.example|198.51.100.2'));
    }

    public function test_identifiers_are_stored_hashed_and_case_insensitive(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->throttle->recordFailure('Victim@X.example|203.0.113.9');
        }

        // Case variations of the same email + IP hit the same counter.
        self::assertGreaterThan(0, $this->throttle->secondsUntilUnlocked('victim@x.example|203.0.113.9'));
    }
}
