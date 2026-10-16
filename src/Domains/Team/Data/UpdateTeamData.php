<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;

/**
 * Input for UpdateTeamAction: the team fields to change. Fields left out are
 * kept. A new `status` stamps `status_changed_at`.
 */
final readonly class UpdateTeamData extends Data
{
    use PreparesStatuses;

    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $slug = new Missing,
        public Status|Missing $status = new Missing,
        public string|Missing|null $statusReason = new Missing,
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
            'status' => ['sometimes', Rule::enum(Statuses::teams())],
            'status_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, ['status' => Statuses::team(...)]);
    }
}
