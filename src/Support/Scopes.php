<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * The organization or team named by request input, for authorizing before
 * validation runs. Unknown slugs resolve to null and fail validation later.
 */
final class Scopes
{
    public static function fromInput(mixed $organization, mixed $team = null): OrganizationModel|TeamModel|null
    {
        $model = self::organization($organization);

        if ($model === null || ! is_string($team) || $team === '') {
            return $model;
        }

        return TeamModel::query()->where('organization_id', $model->getKey())->where('slug', $team)->first() ?? $model;
    }

    /**
     * The organization a slug from input names, or null when it names none.
     */
    public static function organization(mixed $slug): ?OrganizationModel
    {
        return is_string($slug) && $slug !== '' ? OrganizationModel::query()->where('slug', $slug)->first() : null;
    }
}
