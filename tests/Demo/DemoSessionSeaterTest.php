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
}
