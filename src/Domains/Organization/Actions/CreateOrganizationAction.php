<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationCreatedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationCreatingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Slugs;
use RefactorCircus\Roster\Support\Users;

final class CreateOrganizationAction
{
    use ManagesMemberships;
    use OrganizationRules;

    public function __construct(private readonly Users $users) {}

    /**
     * Create an organization; its owner, if any, becomes its first member.
     *
     * `owner` is the owning user's route key. Pass a user model as `$owner`
     * from code to skip the lookup. Without either the organization has no
     * owner until ownership is transferred to a member (as with
     * organizations synced from external systems); personal organizations
     * always need one.
     */
    public function execute(CreateOrganizationData $data, ?Model $owner = null, bool $personal = false): OrganizationModel
    {
        OrganizationCreatingActionEvent::dispatch($data);

        if ($owner === null && $data->owner !== null) {
            $owner = $this->users->findOrFail($data->owner);
        }

        if ($owner === null && $personal) {
            throw ValidationException::withMessages(['owner' => __('roster::roster.personal_needs_owner')]);
        }

        $organization = DB::transaction(function () use ($data, $owner, $personal): OrganizationModel {
            $name = $data->name;
            $slug = $data->slug !== null && $data->slug !== ''
                ? $data->slug
                : Slugs::unique($name, OrganizationModel::query());

            $organization = OrganizationModel::query()->create([
                'name' => $name,
                'slug' => $slug,
                'owner_id' => $owner?->getKey(),
                'personal' => $personal,
                'auto_join' => $data->autoJoin,
                'provisioned_status' => $data->provisionedStatus->value,
            ]);

            $this->syncDomains($organization, $data->domains);

            if ($owner !== null) {
                $this->join($organization, $owner, $personal ? MembershipSource::Personal : MembershipSource::Direct);
            }

            return $organization->load(['domains', 'links'])->loadCount(['memberships', 'teams']);
        });

        OrganizationCreatedActionEvent::dispatch($organization);

        return $organization;
    }
}
