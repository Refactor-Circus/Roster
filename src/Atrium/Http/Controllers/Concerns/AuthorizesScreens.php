<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;

/**
 * The screen the Atrium invitation page declares to Atrium's extension
 * registry.
 */
trait AuthorizesScreens
{
    /**
     * The screen a page declares to Atrium's registry, with the standard
     * slots for its kind. Extensions' permissions are asked globally without
     * a record and in the invitation's organization with one; any other
     * record is refused.
     */
    private function screen(string $key, string $kind, ?Model $record = null): Screen
    {
        return Screen::standard($key, $kind)
            ->record($record)
            ->access(function (string $permission, Request $request, ?Model $record): bool {
                $user = $request->user();
                $user = $user instanceof Model ? $user : null;

                return match (true) {
                    $record === null => app(Authorizer::class)->check($user, $permission),
                    $record instanceof InvitationModel => app(Authorizer::class)->check($user, $permission, $record->organization),
                    default => false,
                };
            });
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
}
