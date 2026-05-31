<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /api/v1/auth/me` — returns the authenticated operator from the verified
 * token claims.
 */
final readonly class MeHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetCurrentUserUseCase $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = AuthContext::userId($request);
        $user = $userId !== null ? $this->useCase->execute($userId) : null;

        if ($user === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'The authenticated user no longer exists.');
        }

        return $this->json->create([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'organization_id' => $user->organizationId,
        ]);
    }
}
