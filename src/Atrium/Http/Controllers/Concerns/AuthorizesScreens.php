<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Roster\Atrium\ScreenAccess;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * The same permission checks the JSON API and MCP make, for Atrium screens,
 * and the screen each page declares to Atrium's extension registry.
 */
trait AuthorizesScreens
{
    private function authorizeScreen(string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): void
    {
        abort_unless(ScreenAccess::allows($permission, $scope, $self), 403);
    }

    /**
     * For lists: without an organization of its own, a user holding the
     * permission only in some organizations still sees those. Returns the
     * organizations to limit the list to, or null for no limit.
     *
     * @return array<int, int|string>|null
     */
    private function authorizeList(string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): ?array
    {
        if (ScreenAccess::allows($permission, $scope, $self)) {
            return null;
        }

        $organizations = $scope === null ? ScreenAccess::organizations($permission) : [];

        abort_if($organizations === null || $organizations === [], 403);

        return $organizations;
    }

    /**
     * The screen a page declares to Atrium's registry, with the standard
     * slots for its kind. Extensions' permissions are asked through
     * ScreenAccess, in the scope the screens use for the record or row.
     */
    private function screen(string $key, string $kind, ?Model $record = null): Screen
    {
        return Screen::standard($key, $kind)
            ->record($record)
            ->access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allowsOn($permission, $record));
    }

    /**
     * The one built-in tab of a screen without tabs of its own: the page.
     *
     * @return list<array{key: string, label: string, icon: string}>
     */
    private function overviewTab(string $icon = TabDefinition::DEFAULT_ICON): array
    {
        return [['key' => Screen::OVERVIEW, 'label' => __('roster::roster.overview'), 'icon' => $icon]];
    }

    /**
     * The signed-in user, for Actions that guard against acting on yourself.
     */
    private function actor(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * The organization a screen's route names, by slug.
     */
    private function organization(string $slug): OrganizationModel
    {
        return OrganizationModel::query()->where('slug', $slug)->firstOrFail();
    }
}
