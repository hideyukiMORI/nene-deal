<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Demo\ProvisionedDemoOrg;
use NeneDeal\Demo\DemoOrgHandles;
use NeneDeal\Demo\DemoSessionSeater;
use NeneDeal\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class DemoSessionSeaterTest extends TestCase
{
    public function test_seat_page_parks_a_login_shaped_token_and_replaces_into_the_spa(): void
    {
        $issuer = new class () implements TokenIssuerInterface {
            /** @var array<string, mixed> */
            public array $claims = [];

            public function issue(array $claims): string
            {
                $this->claims = $claims;

                return 'test-token-abc';
            }
        };

        $handles = new DemoOrgHandles();
        $handle = $handles->register('01ORG00000000000000000000A', '01USR00000000000000000000A', 'demo-seat1');

        $seater = new DemoSessionSeater($issuer, new FixedClock('2026-07-10T00:00:00+00:00'), $handles, new Psr17Factory());
        $request = (new Psr17Factory())->createServerRequest('GET', '/demo/standard');

        $response = $seater->seatAndRedirect($request, new ProvisionedDemoOrg($handle, 'demo-seat1', $handle));

        // Login-shaped claims: sub / role / org / iat / exp with the login TTL.
        self::assertSame('01USR00000000000000000000A', $issuer->claims['sub']);
        self::assertSame('admin', $issuer->claims['role']);
        self::assertSame('01ORG00000000000000000000A', $issuer->claims['org']);
        self::assertSame($issuer->claims['iat'] + 3600, $issuer->claims['exp']);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));

        $html = (string) $response->getBody();
        self::assertStringContainsString("sessionStorage.setItem('nene-deal-demo-seat', \"test-token-abc\")", $html);
        self::assertStringContainsString("location.replace('/')", $html);

        // Page-specific CSP allows exactly the one nonce'd inline script.
        $csp = $response->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("default-src 'none'", $csp);
        self::assertMatchesRegularExpression("/script-src 'nonce-[A-Za-z0-9+\\/=]+'/", $csp);
        self::assertSame(1, preg_match("/'nonce-([A-Za-z0-9+\\/=]+)'/", $csp, $matches));
        $nonce = $matches[1] ?? '';
        self::assertNotSame('', $nonce);
        self::assertStringContainsString('nonce="' . $nonce . '"', $html);
    }

    public function test_demo_entry_logs_referer_and_utm_before_seating(): void
    {
        $lines = [];
        $seater = $this->seater(static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        $request = (new Psr17Factory())
            ->createServerRequest('GET', '/demo/standard?utm_source=facebook&utm_medium=cpc&utm_campaign=summer')
            ->withQueryParams(['utm_source' => 'facebook', 'utm_medium' => 'cpc', 'utm_campaign' => 'summer'])
            ->withHeader('Referer', 'https://www.facebook.com/');

        $seater->seatAndRedirect($request, new ProvisionedDemoOrg(1, 'demo-seat1', 1));

        self::assertCount(1, $lines);
        self::assertStringContainsString('NeNe Deal: demo-entry', $lines[0]);
        self::assertStringContainsString('slug=demo-seat1', $lines[0]);
        self::assertStringContainsString('utm_source=facebook', $lines[0]);
        self::assertStringContainsString('utm_medium=cpc', $lines[0]);
        self::assertStringContainsString('utm_campaign=summer', $lines[0]);
        self::assertStringContainsString('referer=https://www.facebook.com/', $lines[0]);
    }

    public function test_missing_utm_and_referer_render_as_dash(): void
    {
        $lines = [];
        $seater = $this->seater(static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        $request = (new Psr17Factory())->createServerRequest('GET', '/demo/standard');

        $seater->seatAndRedirect($request, new ProvisionedDemoOrg(1, 'demo-seat1', 1));

        self::assertStringContainsString('utm_source=- utm_medium=- utm_campaign=- referer=-', $lines[0]);
    }

    public function test_log_values_are_sanitised_against_injection(): void
    {
        $lines = [];
        $seater = $this->seater(static function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        // The query string is the realistic injection vector (PSR-7 already
        // rejects CR/LF in the Referer *header*). A crafted utm_source must not
        // forge a second log line.
        $request = (new Psr17Factory())
            ->createServerRequest('GET', '/demo/standard')
            ->withQueryParams(['utm_source' => "evil\r\nNeNe Deal: forged"]);

        $seater->seatAndRedirect($request, new ProvisionedDemoOrg(1, 'demo-seat1', 1));

        // A single line only — CR/LF collapsed to spaces, no forged second entry.
        self::assertCount(1, $lines);
        self::assertStringNotContainsString("\n", $lines[0]);
        self::assertStringNotContainsString("\r", $lines[0]);
        self::assertStringContainsString('utm_source=evil NeNe Deal: forged', $lines[0]);
    }

    /**
     * @param \Closure(string): void $logSink
     */
    private function seater(\Closure $logSink): DemoSessionSeater
    {
        $issuer = new class () implements TokenIssuerInterface {
            public function issue(array $claims): string
            {
                return 'test-token-abc';
            }
        };

        $handles = new DemoOrgHandles();
        $handles->register('01ORG00000000000000000000A', '01USR00000000000000000000A', 'demo-seat1');

        return new DemoSessionSeater($issuer, new FixedClock('2026-07-10T00:00:00+00:00'), $handles, new Psr17Factory(), $logSink);
    }
}
