<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;

/**
 * Input for CreateTeamAction: the team's name and, optionally, its slug.
 * Without a slug one is made from the name. `status` is a value of the
 * `roster.statuses.teams` enum; without one the team starts with the enum's
 * default.
 */
final readonly class CreateTeamData extends Data
{
    use PreparesStatuses;

    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?Status $status = null,
        public ?string $statusReason = null,
    ) {}

    /**
     * Pass the organization so the slug is checked within it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?OrganizationModel $organization = null): array
    {
        $slug = Rule::unique(TeamModel::class, 'slug');

        if ($organization !== null) {
            $slug->where(fn (Builder $query): Builder => $query->where('organization_id', $organization->getKey()));
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', $slug],
            'status' => ['sometimes', Rule::enum(Statuses::teams())],
            'status_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, ['status' => Statuses::team(...)]);
    }
}
