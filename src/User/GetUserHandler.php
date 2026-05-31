<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `GET /api/v1/users/{userId}` — returns a single operator. Admin only. */
final readonly class GetUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Router::param($request, 'userId') ?? '';
        $user = $this->users->findById($userId);

        if ($user === null || $user->organizationId !== $this->organization->id()) {
            throw new UserNotFoundException($userId);
        }

        return $this->json->create(UserResponse::toArray($user));
    }
}
