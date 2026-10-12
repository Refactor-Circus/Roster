<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Input for UpdateTeamAction: the team fields to change. Fields left out are
 * kept.
 */
final readonly class UpdateTeamData extends Data
{
    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $slug = new Missing,
    ) {}

    /**
     * Pass the team being updated so its own slug passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?TeamModel $team = null): array
    {
        $slug = Rule::unique(TeamModel::class, 'slug');

        if ($team !== null) {
            $slug->ignore($team->getKey())
                ->where(fn (Builder $query): Builder => $query->where('organization_id', $team->organization_id));
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ];
    }
}
