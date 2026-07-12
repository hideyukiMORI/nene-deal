<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Http;

use NeneDeal\Http\SpaShellFallback;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SpaShellFallbackTest extends TestCase
{
    private string $publicHtml;

    protected function setUp(): void
    {
        $this->publicHtml = sys_get_temp_dir() . '/nene-deal-spa-' . bin2hex(random_bytes(6));
        mkdir($this->publicHtml);
        file_put_contents($this->publicHtml . '/index.html', '<!doctype html><head></head>');
    }

    protected function tearDown(): void
    {
        @unlink($this->publicHtml . '/index.html');
        @rmdir($this->publicHtml);
    }

    private function htmlGet(string $path): ServerRequest
    {
        return (new ServerRequest('GET', $path))->withHeader('Accept', 'text/html,application/xhtml+xml');
    }

    public function test_browser_navigation_gets_the_shell(): void
    {
        $shell = SpaShellFallback::shellPath($this->htmlGet('/some/deep/link'), $this->publicHtml);

        self::assertSame($this->publicHtml . '/index.html', $shell);
    }

    public function test_root_navigation_gets_the_shell(): void
    {
        self::assertSame(
            $this->publicHtml . '/index.html',
            SpaShellFallback::shellPath($this->htmlGet('/'), $this->publicHtml),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function apiAndInfraPaths(): iterable
    {
        yield 'api' => ['/api/v1/deals'];
        yield 'health' => ['/health'];
        yield 'machine' => ['/machine/mcp'];
        yield 'assets' => ['/assets/index-abc.js'];
        yield 'demo start' => ['/demo/standard'];
    }

    #[DataProvider('apiAndInfraPaths')]
    public function test_api_infra_asset_and_demo_paths_are_never_intercepted(string $path): void
    {
        self::assertNull(SpaShellFallback::shellPath($this->htmlGet($path), $this->publicHtml));
    }

    public function test_json_client_is_not_intercepted(): void
    {
        $request = (new ServerRequest('GET', '/dashboard'))->withHeader('Accept', 'application/json');

        self::assertNull(SpaShellFallback::shellPath($request, $this->publicHtml));
    }

    public function test_non_get_is_not_intercepted(): void
    {
        $request = (new ServerRequest('POST', '/dashboard'))->withHeader('Accept', 'text/html');

        self::assertNull(SpaShellFallback::shellPath($request, $this->publicHtml));
    }

    public function test_returns_null_when_shell_is_not_built(): void
    {
        self::assertNull(SpaShellFallback::shellPath($this->htmlGet('/dashboard'), $this->publicHtml . '/does-not-exist'));
    }
}
