<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use RefactorCircus\Roster\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;

/**
 * Runs a SCIM BulkRequest (RFC 7644 §3.7) through the Users and Groups
 * services, resolving `bulkId:` references to the ids created earlier in
 * the same request.
 */
final class Bulk
{
    public function __construct(
        private readonly ScimUsers $users,
        private readonly ScimGroups $groups,
        private readonly ScimMapper $mapper,
    ) {}

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    public function run(array $request, int $payloadBytes): array
    {
        $operations = (array) ($request['Operations'] ?? []);
        $max = (int) config('roster.scim.bulk.max_operations', 100);

        if (count($operations) > $max || $payloadBytes > (int) config('roster.scim.bulk.max_payload_bytes', 1048576)) {
            throw new ScimException(413, "Bulk requests are limited to {$max} operations and ".config('roster.scim.bulk.max_payload_bytes', 1048576).' bytes.', 'tooMany');
        }

        $failOnErrors = is_numeric($request['failOnErrors'] ?? null) ? (int) $request['failOnErrors'] : null;
        $ids = [];
        $errors = 0;
        $results = [];

        foreach ($operations as $operation) {
            if ($failOnErrors !== null && $errors >= $failOnErrors) {
                break;
            }

            $operation = is_array($operation) ? $operation : [];
            $method = strtoupper((string) ($operation['method'] ?? ''));
            $bulkId = is_string($operation['bulkId'] ?? null) ? $operation['bulkId'] : null;

            try {
                $path = (string) $this->resolve((string) ($operation['path'] ?? ''), $ids);
                $data = (array) $this->resolve($operation['data'] ?? [], $ids);
                [$status, $resource] = $this->one($method, $path, $data, is_string($operation['version'] ?? null) ? $operation['version'] : null);

                if ($bulkId !== null && $resource !== null) {
                    $ids[$bulkId] = $resource['id'];
                }

                $results[] = array_filter([
                    'method' => $method,
                    'bulkId' => $bulkId,
                    'location' => $resource['meta']['location'] ?? null,
                    'version' => $resource['meta']['version'] ?? null,
                    'status' => (string) $status,
                ], fn (mixed $value): bool => $value !== null);
            } catch (ScimException $exception) {
                $errors++;
                $results[] = array_filter([
                    'method' => $method,
                    'bulkId' => $bulkId,
                    'status' => (string) $exception->status,
                    'response' => array_filter([
                        'schemas' => [Scim::ERROR],
                        'status' => (string) $exception->status,
                        'scimType' => $exception->scimType,
                        'detail' => $exception->getMessage(),
                    ], fn (mixed $value): bool => $value !== null),
                ], fn (mixed $value): bool => $value !== null);
            }
        }

        return ['schemas' => [Scim::BULK_RESPONSE], 'Operations' => $results];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: int, 1: array<string, mixed>|null}
     */
    private function one(string $method, string $path, array $data, ?string $version): array
    {
        if (preg_match('#^/(Users|Groups)(?:/([^/]+))?$#', $path, $match) !== 1) {
            throw ScimException::invalidPath($path);
        }

        $users = $match[1] === 'Users';
        $id = $match[2] ?? null;

        if ($method === 'POST' && $id === null) {
            $model = $users ? $this->users->create($data) : $this->groups->create($data);

            return [201, $this->present($model)];
        }

        if ($id === null) {
            throw ScimException::invalidPath($path);
        }

        $model = $users ? $this->users->find($id) : $this->groups->find($id);

        // Built only to compare versions: a group's view reads all its members.
        if ($version !== null && ! Scim::matches($version, (string) $this->present($model)['meta']['version'])) {
            throw ScimException::preconditionFailed();
        }

        return match ($method) {
            'PUT' => [200, $this->present($model instanceof ScimUserModel ? $this->users->replace($model, $data) : $this->groups->replace($model, $data))],
            'PATCH' => [200, $this->present($model instanceof ScimUserModel ? $this->users->patch($model, (array) ($data['Operations'] ?? [])) : $this->groups->patch($model, (array) ($data['Operations'] ?? [])))],
            'DELETE' => [204, $this->delete($model)],
            default => throw ScimException::invalidValue("Unsupported bulk method [{$method}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ScimUserModel|ScimGroupModel $model): array
    {
        return $model instanceof ScimUserModel ? $this->mapper->user($model) : $this->mapper->group($model);
    }

    private function delete(ScimUserModel|ScimGroupModel $model): null
    {
        $model instanceof ScimUserModel ? $this->users->delete($model) : $this->groups->delete($model);

        return null;
    }

    /**
     * Replace `bulkId:x` with the id created for that operation.
     *
     * @param  array<string, string>  $ids
     */
    private function resolve(mixed $value, array $ids): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->resolve($item, $ids), $value);
        }

        if (! is_string($value) || ! str_contains($value, 'bulkId:')) {
            return $value;
        }

        return preg_replace_callback('/bulkId:([A-Za-z0-9_\-]+)/', function (array $match) use ($ids): string {
            return $ids[$match[1]] ?? throw ScimException::invalidValue("Unknown bulkId [{$match[1]}].");
        }, $value);
    }
}
