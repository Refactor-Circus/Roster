<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Exporters;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Support\Users;
use RuntimeException;

final class MembersExporter implements Exporter
{
    public function __construct(
        private readonly Users $users,
        private readonly Roles $roles,
    ) {}

    public function headers(): array
    {
        return ['email', 'name', 'display_name', 'status', 'teams', 'roles', 'owner', 'source', 'joined_at'];
    }

    public function rows(TransferModel $transfer, ?string $after, int $limit): array
    {
        $organization = $transfer->organization ?? throw new RuntimeException('A members export needs an organization.');

        $memberships = MembershipModel::query()
            ->where('organization_id', $organization->getKey())
            ->with(['user.rosterProfile', 'teams'])
            ->when($after !== null, fn (Builder $query): Builder => $query->where('id', '>', $after))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $memberships
            ->map(function (MembershipModel $membership) use ($organization): array {
                $user = $membership->user;

                return [
                    'cursor' => $membership->id,
                    'cells' => [
                        $user instanceof Model ? $this->users->email($user) : null,
                        $user instanceof Model ? $this->users->name($user) : null,
                        $user instanceof Model ? ($this->users->profileIfExists($user)->display_name ?? null) : null,
                        $user instanceof Model ? $this->users->status($user)->value : null,
                        $membership->teams->map(fn (TeamModel $team): string => $team->slug)->sort()->implode(';'),
                        // The organization roles the bound role store holds for them.
                        $user instanceof Authenticatable ? implode(';', $this->roles->of($user, $organization)) : '',
                        $user instanceof Model && $organization->isOwnedBy($user),
                        $membership->source,
                        $membership->created_at?->toIso8601String(),
                    ],
                ];
            })
            ->all();
    }
}
