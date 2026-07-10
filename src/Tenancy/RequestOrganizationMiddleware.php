<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves the request's organization and stores its id in the request-scoped
 * holder that every repository reads. Resolution order:
 *
 * 1. Verified bearer-token `org` claim → that organization (404 when it no
 *    longer exists, e.g. a reaped demo org). Users belong to exactly one
 *    organization, so the signed claim is authoritative — it cannot be spoofed
 *    by a header, which also keeps a disposable-demo session (#69) locked to
 *    its own throwaway org. Requires the bearer middleware to run first
 *    ({@see \NeneDeal\Http\RuntimeServiceProvider}).
 * 2. `X-Organization-Slug` header → that organization (404 if unknown).
 * 3. Neither → the sole organization when exactly one is provisioned
 *    (single-tenant convenience; with a disposable demo enabled, orgs are
 *    plural and authenticated traffic resolves via the claim instead).
 *
 * When nothing resolves (e.g. multi-tenant without a header, or no organization
 * yet), the holder is left unset; infra routes like `/health` still work, and
 * data routes that need an organization fail closed downstream.
 */
final readonly class RequestOrganizationMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-Organization-Slug';

    /**
     * @param RequestScopedHolder<string> $orgIdHolder
     */
    public function __construct(
        private RequestScopedHolder $orgIdHolder,
        private OrganizationResolver $resolver,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $claimOrgId = AuthContext::organizationId($request);

        if ($claimOrgId !== null) {
            if (!$this->resolver->existsById($claimOrgId)) {
                return $this->problemDetails->create(
                    $request,
                    'organization-not-found',
                    'Not Found',
                    404,
                    'The organization this session belongs to no longer exists.',
                );
            }

            $this->orgIdHolder->set($claimOrgId);

            return $handler->handle($request);
        }

        $slug = trim($request->getHeaderLine(self::HEADER));

        if ($slug !== '') {
            $id = $this->resolver->findIdBySlug($slug);

            if ($id === null) {
                return $this->problemDetails->create(
                    $request,
                    'organization-not-found',
                    'Not Found',
                    404,
                    sprintf('No organization with slug "%s".', $slug),
                );
            }

            $this->orgIdHolder->set($id);

            return $handler->handle($request);
        }

        $soleId = $this->resolver->soleId();
        if ($soleId !== null) {
            $this->orgIdHolder->set($soleId);
        }

        return $handler->handle($request);
    }
}
