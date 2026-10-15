<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Slugs;
use RefactorCircus\Roster\Support\Statuses;

final class CreateTeamAction
{
    public function execute(OrganizationModel $organization, CreateTeamData $data): TeamModel
    {
        TeamCreatingActionEvent::dispatch($organization, $data);

        $team = DB::transaction(function () use ($organization, $data): TeamModel {
            $name = $data->name;
            $slug = $data->slug !== null && $data->slug !== ''
                ? $data->slug
                : Slugs::unique($name, TeamModel::query()->where('organization_id', $organization->getKey()));

            return TeamModel::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => $name,
                'slug' => $slug,
                'status' => $data->status ?? Statuses::teams()::default(),
                'status_reason' => $data->statusReason,
                'status_changed_at' => $data->status !== null ? now() : null,
            ]);
        });

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamCreatedActionEvent::dispatch($team);

        return $team;
    }
}
