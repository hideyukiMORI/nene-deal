<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email = $body['email'] ?? null;

        if (!is_string($email) || trim($email) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('email', '"email" must be a valid email address.', 'invalid');
        }

        $password = $body['password'] ?? null;

        if (!is_string($password) || strlen($password) < 8) {
            $errors[] = new ValidationError('password', '"password" must be at least 8 characters.', 'invalid');
        }

        $roleValue = $body['role'] ?? null;
        $role = is_string($roleValue) ? OperatorRole::tryFrom($roleValue) : null;

        if ($role === null) {
            $errors[] = new ValidationError('role', '"role" must be one of: admin, operator.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        assert(is_string($email) && is_string($password) && $role instanceof OperatorRole);

        $user = $this->useCase->execute(new CreateUserInput(
            email: $email,
            password: $password,
            role: $role,
        ), AuthContext::userId($request));

        return $this->json->create(UserResponse::toArray($user), 201);
    }
}
