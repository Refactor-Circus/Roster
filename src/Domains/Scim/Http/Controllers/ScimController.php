<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Scim\Services\Bulk;
use RefactorCircus\Roster\Domains\Scim\Services\Discovery;
use RefactorCircus\Roster\Domains\Scim\Services\Scim;
use RefactorCircus\Roster\Domains\Scim\Services\ScimGroups;
use RefactorCircus\Roster\Domains\Scim\Services\ScimMapper;
use RefactorCircus\Roster\Domains\Scim\Services\ScimUsers;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * The SCIM 2.0 protocol endpoints for one organization. A protocol adapter:
 * every change goes through Roster's Actions in the services.
 */
final class ScimController
{
    public function __construct(
        private readonly ScimUsers $users,
        private readonly ScimGroups $groups,
        private readonly ScimMapper $mapper,
    ) {}

    public function listUsers(Request $request): JsonResponse
    {
        [$startIndex, $count] = $this->paging($request);
        [$page, $total] = $this->users->list($this->filter($request), $startIndex, $count);

        return Scim::response($this->mapper->list(array_map(fn (ScimUserModel $user): array => $this->mapper->user($user), $page), $total, $startIndex));
    }

    public function showUser(Request $request, string $organization, string $id): BaseResponse
    {
        return $this->present($request, $this->mapper->user($this->users->find($id)));
    }

    public function createUser(Request $request): JsonResponse
    {
        $resource = $this->mapper->user($this->users->create($this->body($request)));

        return $this->respond($resource, 201);
    }

    public function replaceUser(Request $request, string $organization, string $id): JsonResponse
    {
        $user = $this->users->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->user($user));

        return $this->respond($this->mapper->user($this->users->replace($user, $this->body($request))));
    }

    public function patchUser(Request $request, string $organization, string $id): JsonResponse
    {
        $user = $this->users->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->user($user));

        return $this->respond($this->mapper->user($this->users->patch($user, $this->operations($request))));
    }

    public function deleteUser(Request $request, string $organization, string $id): Response
    {
        $user = $this->users->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->user($user));
        $this->users->delete($user);

        return response()->noContent();
    }

    public function listGroups(Request $request): JsonResponse
    {
        [$startIndex, $count] = $this->paging($request);
        [$page, $total] = $this->groups->list($this->filter($request), $startIndex, $count);

        return Scim::response($this->mapper->list(array_map(fn (ScimGroupModel $group): array => $this->mapper->group($group), $page), $total, $startIndex));
    }

    public function showGroup(Request $request, string $organization, string $id): BaseResponse
    {
        return $this->present($request, $this->mapper->group($this->groups->find($id)));
    }

    public function createGroup(Request $request): JsonResponse
    {
        return $this->respond($this->mapper->group($this->groups->create($this->body($request))), 201);
    }

    public function replaceGroup(Request $request, string $organization, string $id): JsonResponse
    {
        $group = $this->groups->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->group($group));

        return $this->respond($this->mapper->group($this->groups->replace($group, $this->body($request))));
    }

    public function patchGroup(Request $request, string $organization, string $id): JsonResponse
    {
        $group = $this->groups->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->group($group));

        return $this->respond($this->mapper->group($this->groups->patch($group, $this->operations($request))));
    }

    public function deleteGroup(Request $request, string $organization, string $id): Response
    {
        $group = $this->groups->find($id);
        $this->checkVersion($request, fn (): array => $this->mapper->group($group));
        $this->groups->delete($group);

        return response()->noContent();
    }

    public function bulk(Request $request): JsonResponse
    {
        return Scim::response(app(Bulk::class)->run($this->body($request), strlen($request->getContent())));
    }

    public function serviceProviderConfig(Request $request, string $organization): JsonResponse
    {
        return Scim::response(Discovery::serviceProviderConfig($this->base($organization)));
    }

    public function resourceTypes(Request $request, string $organization): JsonResponse
    {
        $types = Discovery::resourceTypes($this->base($organization));

        return Scim::response($this->mapper->list($types, count($types), 1));
    }

    public function schemas(): JsonResponse
    {
        $schemas = Discovery::schemas();

        return Scim::response($this->mapper->list($schemas, count($schemas), 1));
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function present(Request $request, array $resource): BaseResponse
    {
        $version = (string) $resource['meta']['version'];

        if (Scim::matches($request->header('If-None-Match'), $version)) {
            return response('', 304, ['ETag' => $version]);
        }

        return $this->respond($resource);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function respond(array $resource, int $status = 200): JsonResponse
    {
        return Scim::response($resource, $status, array_filter([
            'ETag' => (string) $resource['meta']['version'],
            'Location' => $status === 201 ? (string) $resource['meta']['location'] : null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $current
     */
    /**
     * Refuse a write whose If-Match names another version. The resource is
     * only built when the header is there: most clients never send it, and
     * building a group reads all its members.
     *
     * @param  Closure(): array<string, mixed>  $current
     */
    private function checkVersion(Request $request, Closure $current): void
    {
        $ifMatch = $request->header('If-Match');

        if ($ifMatch !== null && ! Scim::matches($ifMatch, (string) $current()['meta']['version'])) {
            throw ScimException::preconditionFailed();
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function paging(Request $request): array
    {
        $startIndex = max(1, (int) $request->query('startIndex', '1'));
        $count = (int) $request->query('count', (string) config('roster.scim.default_count', 100));

        return [$startIndex, max(0, min($count, (int) config('roster.scim.max_count', 500)))];
    }

    private function filter(Request $request): ?string
    {
        $filter = $request->query('filter');

        return is_string($filter) ? $filter : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function body(Request $request): array
    {
        $body = $request->json()->all();

        if ($body === []) {
            throw ScimException::invalidValue('The request body must be a JSON object.');
        }

        return $body;
    }

    /**
     * @return array<int, mixed>
     */
    private function operations(Request $request): array
    {
        return array_values((array) ($this->body($request)['Operations'] ?? []));
    }

    private function base(string $organization): string
    {
        return url(trim((string) config('roster.scim.prefix', 'scim/v2'), '/').'/'.$organization);
    }
}
