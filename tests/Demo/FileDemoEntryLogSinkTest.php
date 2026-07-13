<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use NeneDeal\Demo\FileDemoEntryLogSink;
use PHPUnit\Framework\TestCase;

final class FileDemoEntryLogSinkTest extends TestCase
{
    private string $baseDir;
    private string $originalErrorLog;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/nene-deal-demo-entry-log-test-' . bin2hex(random_bytes(6));
        $this->originalErrorLog = (string) ini_get('error_log');
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->originalErrorLog);
        @unlink($this->baseDir . '.errors');

        if (is_dir($this->baseDir)) {
            @unlink($this->baseDir . '/demo-entry.log');
            @rmdir($this->baseDir);
        } else {
            @unlink($this->baseDir);
        }
    }

    public function test_appends_the_line_to_demo_entry_log(): void
    {
        $sink = FileDemoEntryLogSink::toFile($this->baseDir);

        $sink('NeNe Deal: demo-entry slug=demo-seat1 utm_source=facebook');

        $contents = file_get_contents($this->baseDir . '/demo-entry.log');
        self::assertSame("NeNe Deal: demo-entry slug=demo-seat1 utm_source=facebook\n", $contents);
    }

    public function test_appends_multiple_lines_without_overwriting(): void
    {
        $sink = FileDemoEntryLogSink::toFile($this->baseDir);

        $sink('line one');
        $sink('line two');

        $contents = file_get_contents($this->baseDir . '/demo-entry.log');
        self::assertSame("line one\nline two\n", $contents);
    }

    public function test_creates_the_base_directory_when_missing(): void
    {
        self::assertDirectoryDoesNotExist($this->baseDir);

        $sink = FileDemoEntryLogSink::toFile($this->baseDir);
        $sink('line one');

        self::assertDirectoryExists($this->baseDir);
        self::assertFileExists($this->baseDir . '/demo-entry.log');
    }

    public function test_falls_back_to_error_log_when_the_base_dir_cannot_be_used(): void
    {
        // A regular file occupies the would-be base directory path, so both
        // `mkdir($baseDir, ...)` and `fopen("$baseDir/demo-entry.log", 'a')`
        // fail — the same failure shape as an unwritable `var/` on a
        // misconfigured deployment. Root-proof (unlike chmod 0000): no
        // permission bit is relied on.
        file_put_contents($this->baseDir, 'not a directory');

        $errorLogFile = $this->baseDir . '.errors';
        ini_set('error_log', $errorLogFile);

        $sink = FileDemoEntryLogSink::toFile($this->baseDir);
        $sink('NeNe Deal: demo-entry slug=demo-seat1');

        self::assertFileExists($errorLogFile);
        self::assertStringContainsString(
            'NeNe Deal: demo-entry slug=demo-seat1',
            (string) file_get_contents($errorLogFile),
        );
    }
}
