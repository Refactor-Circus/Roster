<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;

/**
 * Input for CreateOrganizationAction: the organization and its settings.
 *
 * Without a slug one is made from the name. `owner` is the owning user's
 * route key. `provisioned_status` is the status its externally provisioned
 * accounts start with: active (the default), or waiting for approval. `status`
 * is the organization's own, from `roster.statuses.organizations`; without
 * one it starts with the enum's default.
 */
final readonly class CreateOrganizationData extends Data
{
    use OrganizationRules;
    use PreparesStatuses;

    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public int|string|null $owner = null,
        public bool $autoJoin = false,
        public ?AccountStatus $provisionedStatus = null,
        public array $domains = [],
        public ?Status $status = null,
        public ?string $statusReason = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $users = app(Users::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', Rule::unique(OrganizationModel::class, 'slug')],
            'owner' => ['sometimes', 'nullable', Rule::exists($users->table(), $users->routeKeyName())],
        ] + self::settingsRules();
    }

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, [
            'provisioned_status' => Statuses::user(...),
            'status' => Statuses::organization(...),
        ]);
    }
}
