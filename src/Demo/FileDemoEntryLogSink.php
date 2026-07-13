<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

/**
 * Builds the file-backed sink for the demo-entry attribution line (#112
 * layer 1): appends to `<baseDir>/demo-entry.log`, using the same
 * base-directory resolution (`is_dir` + best-effort `mkdir`) as
 * {@see FileRateLimitStorage}.
 *
 * Exists because {@see \NeneDeal\Demo\DemoSessionSeater}'s default sink is
 * PHP's `error_log`, which on the Tier A HETEML shared-hosting target routes
 * to the control-panel log — invisible over SSH and unsuited to grep-based
 * UTM analysis. `var/` is the only writable runtime directory there, so this
 * sink lands the attribution line where it can actually be tailed
 * (`tail -f var/demo-entry.log`).
 *
 * Best-effort: if the directory cannot be created, or the file cannot be
 * opened, locked or written, the line falls back to `error_log` — a
 * misconfigured `var/` never drops the entry, it just downgrades to the old
 * default.
 */
final class FileDemoEntryLogSink
{
    private function __construct()
    {
    }

    /**
     * @return \Closure(string): void
     */
    public static function toFile(string $baseDir): \Closure
    {
        return static function (string $line) use ($baseDir): void {
            if (!is_dir($baseDir)) {
                @mkdir($baseDir, 0o775, true);
            }

            $file = $baseDir . '/demo-entry.log';
            $handle = @fopen($file, 'a');

            if ($handle === false) {
                error_log($line);

                return;
            }

            try {
                if (!flock($handle, LOCK_EX) || @fwrite($handle, $line . PHP_EOL) === false) {
                    error_log($line);

                    return;
                }

                fflush($handle);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        };
    }
}
