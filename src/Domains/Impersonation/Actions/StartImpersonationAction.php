<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartedImpersonation;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStartedActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStartingActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;
use RefactorCircus\Roster\Support\Users;

final class StartImpersonationAction
{
    use ResolvesScopes;

    public function __construct(
        private readonly Users $users,
        private readonly Permissions $permissions,
    ) {}

    /**
     * Issue a one-time link that lets `$actor` act as `$user`.
     *
     * The link only works in a browser signed in as `$actor`. Nobody can
     * impersonate themselves, an inactive user, a super-admin (unless they
     * are one), or anyone holding a permission they lack.
     */
    public function execute(Model $user, StartImpersonationData $data, Model $actor): StartedImpersonation
    {
        $organization = $this->organizationFrom($data->organization);

        $this->guard($user, $actor, $organization);

        ImpersonationStartingActionEvent::dispatch($user, $data);

        $token = Str::random(48);
        $linkExpires = now()->addMinutes((int) config('roster.impersonation.link_minutes', 5));

        $impersonation = DB::transaction(fn (): ImpersonationModel => ImpersonationModel::query()->create([
            'impersonator_id' => $actor->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => $organization?->getKey(),
            'reason' => $data->reason,
            'token_hash' => hash('sha256', $token),
            'link_expires_at' => $linkExpires,
        ]));

        ImpersonationStartedActionEvent::dispatch($impersonation);

        return new StartedImpersonation(
            $impersonation->load(['user', 'impersonator', 'organization']),
            URL::temporarySignedRoute('roster.impersonation.enter', $linkExpires, ['token' => $token]),
        );
    }

    private function guard(Model $user, Model $actor, ?OrganizationModel $organization): void
    {
        $fail = fn (string $key) => throw ValidationException::withMessages(['user' => __('roster::roster.'.$key)]);

        if ($actor->is($user)) {
            $fail('impersonate_self');
        }

        if (app(ImpersonationContext::class)->active() !== null) {
            $fail('impersonate_nested');
        }

        if ($this->users->status($user) !== UserStatus::Active) {
            $fail('impersonate_inactive');
        }

        if ($organization !== null && $organization->membershipFor($user) === null) {
            $fail('not_a_member');
        }

        if ($this->permissions->isSuperAdmin($user) && ! $this->permissions->isSuperAdmin($actor)) {
            $fail('impersonate_super');
        }

        // Acting as someone must never hand you powers you do not have.
        $missing = array_diff($this->permissions->for($user, $organization), $this->permissions->for($actor, $organization));

        if ($missing !== [] && ! $this->permissions->isSuperAdmin($actor)) {
            $fail('impersonate_escalation');
        }
    }
}
