<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Http;

use NeneDeal\Http\DemoAnalyticsInjection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DemoAnalyticsInjectionTest extends TestCase
{
    private const string SHELL = '<!doctype html><html><head><title>Deal</title></head><body><div id="root"></div></body></html>';

    public function test_disabled_when_env_is_unset(): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv([]);

        self::assertFalse($analytics->isEnabled());
        self::assertNull($analytics->contentSecurityPolicy());
        self::assertSame('', $analytics->beaconTag());
    }

    public function test_disabled_when_env_is_empty_string(): void
    {
        self::assertFalse(DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => ''])->isEnabled());
        self::assertFalse(DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => '   '])->isEnabled());
    }

    public function test_disabled_shell_is_byte_for_byte_identical(): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv([]);

        self::assertSame(self::SHELL, $analytics->injectHead(self::SHELL));
    }

    public function test_enabled_injects_beacon_before_head_close(): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => 'https://stats.ayane.co.jp']);

        self::assertTrue($analytics->isEnabled());

        $out = $analytics->injectHead(self::SHELL);

        self::assertStringContainsString(
            '<script data-goatcounter="https://stats.ayane.co.jp/count" async src="https://stats.ayane.co.jp/count.js"></script>',
            $out,
        );
        // Beacon lands inside <head> (before the closing tag), not in the body.
        self::assertLessThan(strpos($out, '</head>'), strpos($out, 'data-goatcounter'));
        // Everything else is untouched.
        self::assertStringContainsString('<title>Deal</title>', $out);
        self::assertStringContainsString('<div id="root"></div>', $out);
    }

    public function test_trailing_slash_is_trimmed_to_a_bare_origin(): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => 'https://stats.ayane.co.jp/']);

        self::assertFalse($analytics->isEnabled(), 'A trailing slash makes the value a non-bare origin and must be rejected.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEndpointProvider(): iterable
    {
        yield 'path present' => ['https://stats.ayane.co.jp/count'];
        yield 'query present' => ['https://stats.ayane.co.jp?a=1'];
        yield 'fragment present' => ['https://stats.ayane.co.jp#x'];
        yield 'http scheme rejected' => ['http://stats.ayane.co.jp'];
        yield 'no scheme' => ['stats.ayane.co.jp'];
        yield 'credentials' => ['https://user:pass@stats.ayane.co.jp'];
        yield 'internal whitespace' => ['https://stats ayane.co.jp'];
        yield 'quote injection' => ['https://stats.ayane.co.jp"onerror=x'];
        yield 'angle bracket' => ['https://stats.ayane.co.jp<script>'];
    }

    #[DataProvider('invalidEndpointProvider')]
    public function test_invalid_endpoints_disable_analytics(string $value): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => $value]);

        self::assertFalse($analytics->isEnabled(), sprintf('Value %s must be rejected (fail-safe).', $value));
        self::assertSame(self::SHELL, $analytics->injectHead(self::SHELL));
    }

    public function test_bare_origin_with_port_is_accepted(): void
    {
        $analytics = DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => 'https://stats.example.test:8443']);

        self::assertTrue($analytics->isEnabled());
        self::assertStringContainsString('https://stats.example.test:8443/count.js', $analytics->beaconTag());
    }

    public function test_csp_widens_only_script_img_connect_and_preserves_google_fonts(): void
    {
        $csp = DemoAnalyticsInjection::fromEnv(['DEMO_ANALYTICS_ENDPOINT' => 'https://stats.ayane.co.jp'])->contentSecurityPolicy();

        self::assertNotNull($csp);
        // Analytics origin added to exactly the three GoatCounter directives.
        self::assertStringContainsString("script-src 'self' https://stats.ayane.co.jp;", $csp);
        self::assertStringContainsString("img-src 'self' data: https://stats.ayane.co.jp;", $csp);
        self::assertStringContainsString("connect-src 'self' https://stats.ayane.co.jp;", $csp);
        // Deal's Google Fonts + inline-style directives are preserved verbatim
        // (the clear #612 trap: dropping these breaks the React/Vite demo).
        self::assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;", $csp);
        self::assertStringContainsString("font-src 'self' data: https://fonts.gstatic.com;", $csp);
        // Untouched locked-down directives.
        self::assertStringContainsString("default-src 'self';", $csp);
        self::assertStringContainsString("object-src 'none';", $csp);
        self::assertStringContainsString("frame-ancestors 'self'", $csp);
        // The origin never leaks into a directive it does not belong in.
        self::assertStringNotContainsString("font-src 'self' data: https://fonts.gstatic.com https://stats", $csp);
        self::assertStringNotContainsString("default-src 'self' https://stats", $csp);
    }
}
