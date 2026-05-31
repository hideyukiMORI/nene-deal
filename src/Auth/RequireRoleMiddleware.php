<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneDeal\User\OperatorRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rejects requests whose JWT `role` claim is below the required role.
 * Must run after the bearer-token verification middleware.
 */
final readonly class RequireRoleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private OperatorRole $required,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $roleValue = AuthContext::role($request);

        if ($roleValue === null || !$this->hasRole(OperatorRole::tryFrom($roleValue))) {
            return $this->problemDetails->create(
                $request,
                'forbidden',
                'Forbidden',
                403,
                'Insufficient role to perform this action.',
            );
        }

        return $handler->handle($request);
    }

    private function hasRole(?OperatorRole $actual): bool
    {
        if ($actual === null) {
            return false;
        }

        return match ($this->required) {
            OperatorRole::Admin => $actual === OperatorRole::Admin,
            OperatorRole::Operator => true,
        };
    }
}
