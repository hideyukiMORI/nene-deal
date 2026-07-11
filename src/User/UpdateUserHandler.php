<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email = null;

        if (array_key_exists('email', $body)) {
            $value = $body['email'];

            if (!is_string($value) || trim($value) === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = new ValidationError('email', '"email" must be a valid email address.', 'invalid');
            } else {
                $email = $value;
            }
        }

        $role = null;

        if (array_key_exists('role', $body)) {
            $role = is_string($body['role']) ? OperatorRole::tryFrom($body['role']) : null;

            if ($role === null) {
                $errors[] = new ValidationError('role', '"role" must be one of: admin, operator.', 'invalid');
            }
        }

        $status = null;

        if (array_key_exists('status', $body)) {
            $status = is_string($body['status']) ? UserStatus::tryFrom($body['status']) : null;

            if ($status === null) {
                $errors[] = new ValidationError('status', '"status" must be one of: active, disabled.', 'invalid');
            }
        }

        if ($errors === [] && $email === null && $role === null && $status === null) {
            $errors[] = new ValidationError('body', 'At least one of "email", "role" or "status" is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $targetUserId = Router::param($request, 'userId') ?? '';
        $actorUserId = AuthContext::userId($request) ?? '';

        $user = $this->useCase->execute($targetUserId, $actorUserId, new UpdateUserInput($email, $role, $status));

        return $this->json->create(UserResponse::toArray($user));
    }
}
