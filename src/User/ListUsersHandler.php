<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `GET /api/v1/users` — lists all operators in the organization. Admin only. */
final readonly class ListUsersHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListUsersUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $users = $this->useCase->execute();

        return $this->json->create([
            'data' => array_map(
                static fn (User $user): array => UserResponse::toArray($user),
                $users,
            ),
        ]);
    }
}
