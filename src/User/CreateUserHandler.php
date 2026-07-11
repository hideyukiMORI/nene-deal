<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `POST /api/v1/users` — creates an operator in the organization. Admin only. */
final readonly class CreateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateUserUseCase $useCase,
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

        $email = $body['email'] ?? null;

        if (!is_string($email) || trim($email) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"email" must be a valid email address.');
        }

        $password = $body['password'] ?? null;

        if (!is_string($password) || strlen($password) < 8) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"password" must be at least 8 characters.');
        }

        $roleValue = $body['role'] ?? null;
        $role = is_string($roleValue) ? OperatorRole::tryFrom($roleValue) : null;

        if ($role === null) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"role" must be one of: admin, operator.');
        }

        $user = $this->useCase->execute(new CreateUserInput(
            email: $email,
            password: $password,
            role: $role,
        ), AuthContext::userId($request));

        return $this->json->create(UserResponse::toArray($user), 201);
    }
}
