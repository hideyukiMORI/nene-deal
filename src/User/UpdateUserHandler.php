<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `PATCH /api/v1/users/{userId}` — updates role, email or status. Admin only. */
final readonly class UpdateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateUserUseCase $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'Request body must be a JSON object.');
        }

        $email = null;

        if (array_key_exists('email', $body)) {
            $email = $body['email'];

            if (!is_string($email) || trim($email) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"email" must be a valid email address.');
            }
        }

        $role = null;

        if (array_key_exists('role', $body)) {
            $role = is_string($body['role']) ? OperatorRole::tryFrom($body['role']) : null;

            if ($role === null) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"role" must be one of: admin, operator.');
            }
        }

        $status = null;

        if (array_key_exists('status', $body)) {
            $status = is_string($body['status']) ? UserStatus::tryFrom($body['status']) : null;

            if ($status === null) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"status" must be one of: active, disabled.');
            }
        }

        if ($email === null && $role === null && $status === null) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'At least one of "email", "role" or "status" is required.');
        }

        $targetUserId = Router::param($request, 'userId') ?? '';
        $actorUserId = AuthContext::userId($request) ?? '';

        $user = $this->useCase->execute($targetUserId, $actorUserId, new UpdateUserInput($email, $role, $status));

        return $this->json->create(UserResponse::toArray($user));
    }
}
