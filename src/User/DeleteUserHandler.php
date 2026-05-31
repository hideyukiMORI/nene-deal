<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `DELETE /api/v1/users/{userId}` — deletes an operator. Admin only. */
final readonly class DeleteUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteUserUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $targetUserId = Router::param($request, 'userId') ?? '';
        $actorUserId = AuthContext::userId($request) ?? '';

        $this->useCase->execute($targetUserId, $actorUserId);

        return $this->json->createEmpty(204);
    }
}
