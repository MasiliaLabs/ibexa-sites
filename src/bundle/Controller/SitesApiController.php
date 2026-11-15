<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Masilia\Contracts\IbexaSites\Problem;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteOptions;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteSettings;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Ibexa\LocationLookup;
use Masilia\IbexaSites\Input\SiteInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * What the scripts of the sites page ask for, as JSON: the sites with the choices to make one, a check of a site field by field,
 * and the writes. Seen with masilia_site/view, changed with masilia_site/manage. A write carries the CSRF token of the page in the
 * X-CSRF-Token header (see CsrfTokenSubscriber).
 */
#[Route('/api/masilia/sites')]
final class SitesApiController extends Controller
{
    public const ROUTE_PREFIX = 'masilia.ibexa_sites.api.';

    public function __construct(
        private readonly SiteRegistry $registry,
        private readonly SiteSettings $settings,
        private readonly SiteOptions $options,
        private readonly LocationLookup $locations,
    ) {
    }

    public function performAccessCheck(): void
    {
        parent::performAccessCheck();
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'view'));
    }

    #[Route('/data', name: self::ROUTE_PREFIX . 'data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        return new JsonResponse([
            'canManage' => $this->isGranted(new Attribute('masilia_site', 'manage')),
            'sites' => array_map(static fn (Site $site): array => $site->toArray(), $this->registry->list()),
            'options' => [
                'languages' => $this->options->languages(),
                'designs' => $this->options->designs(),
                'baseGroups' => $this->options->baseGroups(),
                'adminSiteaccesses' => $this->options->adminSiteaccesses(),
            ],
            'defaultSiteaccess' => $this->settings->defaultSiteaccess(),
            'defaultChoices' => $this->settings->defaultSiteaccessChoices(),
        ]);
    }

    #[Route('/locations/{id}', name: self::ROUTE_PREFIX . 'location', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function location(int $id): JsonResponse
    {
        $location = $this->locations->describe($id);

        return $location === null ? $this->error('not_found', sprintf('The location %d does not exist, or you cannot see it.', $id), Response::HTTP_NOT_FOUND) : new JsonResponse($location);
    }

    /** What is wrong with a site as it is being written, field by field: nothing is saved. */
    #[Route('/validate', name: self::ROUTE_PREFIX . 'validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $body = $this->body($request);
        if ($body === null) {
            return $this->error('bad_request', 'The body must be a JSON object.', Response::HTTP_BAD_REQUEST);
        }
        $isNew = (bool) ($body['isNew'] ?? false);
        $identifier = trim((string) ($body['site']['identifier'] ?? $body['identifier'] ?? ''));
        $current = $isNew ? null : $this->registry->find($identifier);
        if (!$isNew && $current === null) {
            return $this->error('not_found', sprintf('No site "%s".', $identifier), Response::HTTP_NOT_FOUND);
        }
        $status = (bool) ($body['asActive'] ?? false) ? SiteStatus::Active : ($current !== null ? $current->status : SiteStatus::Draft);

        return new JsonResponse(['problems' => $this->items($this->registry->check(SiteInput::site((array) ($body['site'] ?? []), $identifier, $status), $isNew))]);
    }

    #[Route('', name: self::ROUTE_PREFIX . 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $body = $this->body($request);
        if ($body === null) {
            return $this->error('bad_request', 'The body must be a JSON object.', Response::HTTP_BAD_REQUEST);
        }
        try {
            // A site starts as a draft: it is served when it is activated, which is a decision of its own.
            $site = $this->registry->create(SiteInput::site($body, (string) ($body['identifier'] ?? ''), SiteStatus::Draft));
        } catch (SiteInvalid $invalid) {
            return $this->invalid($invalid);
        }

        return new JsonResponse(['site' => $site->toArray()], Response::HTTP_CREATED);
    }

    #[Route('/{identifier}', name: self::ROUTE_PREFIX . 'update', requirements: ['identifier' => '[a-z][a-z0-9_]+'], methods: ['PUT'])]
    public function update(Request $request, string $identifier): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $body = $this->body($request);
        $current = $this->registry->find($identifier);
        if ($current === null) {
            return $this->error('not_found', sprintf('No site "%s".', $identifier), Response::HTTP_NOT_FOUND);
        }
        if ($body === null) {
            return $this->error('bad_request', 'The body must be a JSON object.', Response::HTTP_BAD_REQUEST);
        }
        try {
            $site = $this->registry->update(SiteInput::site($body, $identifier, $current->status));
        } catch (SiteInvalid $invalid) {
            return $this->invalid($invalid);
        } catch (SiteNotFound) {
            return $this->error('not_found', sprintf('No site "%s".', $identifier), Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['site' => $site->toArray()]);
    }

    #[Route('/{identifier}/status', name: self::ROUTE_PREFIX . 'status', requirements: ['identifier' => '[a-z][a-z0-9_]+'], methods: ['PUT'])]
    public function status(Request $request, string $identifier): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $status = SiteStatus::tryFrom((string) ($this->body($request)['status'] ?? ''));
        if ($status === null || $status === SiteStatus::Draft) {
            return $this->error('bad_request', 'The status is "active" or "disabled".', Response::HTTP_BAD_REQUEST);
        }
        try {
            $site = $this->registry->setStatus($identifier, $status);
        } catch (SiteInvalid $invalid) {
            return $this->invalid($invalid);
        } catch (SiteNotFound) {
            return $this->error('not_found', sprintf('No site "%s".', $identifier), Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['site' => $site->toArray()]);
    }

    #[Route('/{identifier}', name: self::ROUTE_PREFIX . 'delete', requirements: ['identifier' => '[a-z][a-z0-9_]+'], methods: ['DELETE'])]
    public function delete(string $identifier): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        try {
            $this->registry->delete($identifier);
        } catch (SiteInvalid $invalid) {
            return $this->invalid($invalid);
        } catch (SiteNotFound) {
            return $this->error('not_found', sprintf('No site "%s".', $identifier), Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['identifier' => $identifier]);
    }

    /** The siteaccess that answers when no host matches: a name, or null for the project's. */
    #[Route('/settings/default', name: self::ROUTE_PREFIX . 'default', methods: ['PUT'])]
    public function defaultSiteaccess(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(new Attribute('masilia_site', 'manage'));
        $chosen = trim((string) ($this->body($request)['siteaccess'] ?? ''));
        try {
            $this->settings->setDefaultSiteaccess($chosen === '' ? null : $chosen);
        } catch (SiteInvalid $invalid) {
            return $this->invalid($invalid);
        }

        return new JsonResponse(['defaultSiteaccess' => $this->settings->defaultSiteaccess()]);
    }

    /** @return array<string, mixed>|null */
    private function body(Request $request): ?array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param list<Problem> $problems
     *
     * @return list<array{path: string, message: string}>
     */
    private function items(array $problems): array
    {
        return array_map(static fn (Problem $p): array => ['path' => $p->path, 'message' => $p->message], $problems);
    }

    private function invalid(SiteInvalid $invalid): JsonResponse
    {
        $items = $invalid->items !== [] ? $this->items($invalid->items) : array_map(static fn (string $m): array => ['path' => '', 'message' => $m], $invalid->problems);

        return $this->error('invalid', implode(' ', $invalid->problems), Response::HTTP_BAD_REQUEST, $items);
    }

    /** @param list<array{path: string, message: string}> $problems */
    private function error(string $code, string $message, int $status, array $problems = []): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $code, 'message' => $message], 'problems' => $problems], $status);
    }
}
