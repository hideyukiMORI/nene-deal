<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Records whether it was invoked; returns a 200 response. Used to assert
 * middleware short-circuit behaviour.
 */
final class RecordingRequestHandler implements RequestHandlerInterface
{
    public bool $called = false;

    public function __construct(
        private readonly Psr17Factory $psr17 = new Psr17Factory(),
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->called = true;

        return $this->psr17->createResponse(200);
    }
}
