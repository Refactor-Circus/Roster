<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\DomainJoinedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\DomainJoiningActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class JoinOrganizationsByDomainAction
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    /**
     * Join the user to every auto-join organization that owns their email's
     * domain, returning the organizations newly joined.
     *
     * Users whose email is not verified join nothing: anyone can type an
     * address on someone else's domain.
     *
     * @return Collection<int, OrganizationModel>
     */
    public function execute(Model $user): Collection
    {
        $email = $this->users->email($user);

        if ($email === null || ! str_contains($email, '@') || ! $this->users->emailVerified($user)) {
            return new Collection;
        }

        DomainJoiningActionEvent::dispatch($user);

        $domain = strtolower(substr($email, strrpos($email, '@') + 1));

        $joined = DB::transaction(function () use ($user, $domain): Collection {
            $organizations = OrganizationModel::query()
                ->where('auto_join', true)
                ->whereHas('domains', fn (Builder $query): Builder => $query->where('domain', $domain))
                ->whereDoesntHave('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
                ->get();

            foreach ($organizations as $organization) {
                $this->join($organization, $user, MembershipSource::Domain);
            }

            return $organizations;
        });

        // Everything OrganizationResource reads, so the response costs no query per organization.
        $joined->load(['owner', 'domains', 'links'])->loadCount(['memberships', 'teams']);

        DomainJoinedActionEvent::dispatch($user, $joined->pluck('slug')->all());

        return $joined;
    }
}
