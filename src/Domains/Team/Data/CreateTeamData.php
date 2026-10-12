<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Input for CreateTeamAction: the team's name and, optionally, its slug.
 * Without a slug one is made from the name.
 */
final readonly class CreateTeamData extends Data
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
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
        ];
    }
}
