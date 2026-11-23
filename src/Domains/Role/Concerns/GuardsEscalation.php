<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Authorization\Authorizer;

/**
 * Stops anyone handing out more than they hold: an actor may only grant
 * permissions - by building a role or assigning one - that they have
 * themselves in that scope. Code calls without an actor are trusted.
 */
trait GuardsEscalation
{
    /**
     * @param  array<int, string>  $permissions
     */
    private function guardEscalation(?Model $actor, array $permissions, OrganizationModel|TeamModel|null $scope, string $field): void
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return;
        }

        $held = app(Permissions::class)->for($actor, $scope);
        $missing = array_values(array_diff($permissions, $held));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                $field => __('roster::roster.cannot_grant', ['permissions' => implode(', ', $missing)]),
            ]);
        }
    }
}
