<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /api/v1/auth/login` — public. Exchanges email + password for a bearer token.
 */
final readonly class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private LoginUseCase $useCase,
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
        $password = $body['password'] ?? null;

        if (!is_string($email) || $email === '' || !is_string($password) || $password === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'Both "email" and "password" are required.');
        }

        $output = $this->useCase->execute(new LoginInput($email, $password));

        return $this->json->create(['token' => $output->token]);
    }
}
