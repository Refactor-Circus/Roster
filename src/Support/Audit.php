<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\TransferContext;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Roster;

/**
 * What Roster teaches the suite-wide audit log (refactor-circus/keen) about its models
 * and events, through refactor-circus/keystone's AuditHooks.
 *
 * Registered whether or not Keen is installed; the hooks cost nothing until
 * it records. Subjects and scopes are picked for Roster's own events only;
 * the impersonator and import context applies to every entry, as
 * a change made while impersonating is the impersonator's whatever package
 * made it.
 */
final readonly class Audit
{
    /** @var array<int, string> */
    private const array PROFILE_SKIPPED = ['id', 'user_id', 'created_at', 'updated_at'];

    public function __construct(
        private Users $users,
        private PackageRegistry $packages,
    ) {}

    public function register(AuditHooks $hooks): void
    {
        $this->models($hooks);

        $hooks->subject(fn (object $event, array $models): ?Model => $this->ownsEvent($event) ? $this->subject($models) : null);
        $hooks->context(fn (object $event, ?Model $subject): array => $this->context());
        $hooks->scope(fn (object $event, array $models, ?Model $subject): ?Model => $this->ownsEvent($event) ? $this->organization($models, $subject) : null);

        $password = $this->users->column('password');

        if ($password !== null) {
            $hooks->redact($password);
        }
    }

    /**
     * The classes Roster names and snapshots, besides the user model.
     *
     * @var array<int, class-string<Model>>
     */
    private const array MODELS = [
        OrganizationModel::class,
        TeamModel::class,
        RoleModel::class,
        InvitationModel::class,
        RoleAssignmentModel::class,
        TransferModel::class,
    ];

    /**
     * Name Roster's records and the user model it manages, and add the
     * related records whose changes belong in their diffs.
     */
    private function models(AuditHooks $hooks): void
    {
        $user = $this->userModel();

        foreach ($user === null ? self::MODELS : [$user, ...self::MODELS] as $class) {
            $hooks->label($class, $this->label(...))->snapshot($class, $this->snapshot(...));
        }
    }

    private function label(Model $model): ?string
    {
        return match (true) {
            $model instanceof OrganizationModel, $model instanceof TeamModel, $model instanceof RoleModel => $model->name,
            $model instanceof InvitationModel => $model->email,
            $model instanceof RoleAssignmentModel => $model->role?->name,
            $model instanceof TransferModel => $model->type->label(),
            default => $this->users->name($model) ?? $this->users->email($model) ?? (string) $model->getKey(),
        };
    }

    /**
     * A user's profile fields, a role's permissions, an organization's
     * domains, and what a role assignment grants where.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Model $model): array
    {
        return match (true) {
            $model instanceof RoleModel => ['permissions' => $model->permissions()->orderBy('name')->pluck('name')->all()],
            $model instanceof OrganizationModel => ['domains' => $model->domains()->orderBy('domain')->pluck('domain')->map(fn (mixed $domain): string => (string) $domain)->all()],
            $model instanceof RoleAssignmentModel => [
                'role' => $model->role?->slug,
                'organization' => $model->organization?->slug,
                'team' => $model->team?->slug,
            ],
            $model instanceof TeamModel, $model instanceof InvitationModel, $model instanceof TransferModel => [],
            default => $this->profile($model),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(Model $user): array
    {
        $profile = ProfileModel::query()->where('user_id', $user->getKey())->first();
        $values = [];

        foreach (array_keys($profile?->getAttributes() ?? []) as $key) {
            if (! in_array($key, self::PROFILE_SKIPPED, true)) {
                $values['profile.'.$key] = $profile?->getAttribute($key);
            }
        }

        return $values;
    }

    /**
     * An entry is about a user when one is involved: being given a role,
     * being impersonated. Otherwise the event's `user`, else the audit log's
     * own choice (the event's first model).
     *
     * @param  array<string, Model>  $models
     */
    private function subject(array $models): ?Model
    {
        $assignment = $models['assignment'] ?? null;

        if ($assignment instanceof RoleAssignmentModel && $assignment->user instanceof Model) {
            return $assignment->user;
        }

        $impersonation = $models['impersonation'] ?? null;

        if ($impersonation instanceof ImpersonationModel && $impersonation->user instanceof Model) {
            return $impersonation->user;
        }

        return $models['user'] ?? null;
    }

    /**
     * Who was really acting when the change came from an impersonated
     * session, and the import it came through. Other packages add their own
     * context on AuditHooks.
     *
     * @return array<string, mixed>
     */
    private function context(): array
    {
        $context = [];

        $impersonator = app(ImpersonationContext::class)->impersonator();

        if ($impersonator !== null) {
            $context['impersonator'] = [
                'id' => (string) $impersonator->getKey(),
                'label' => $this->users->name($impersonator) ?? $this->users->email($impersonator) ?? (string) $impersonator->getKey(),
            ];
        }

        $transfer = app(TransferContext::class)->transfer;

        if ($transfer !== null) {
            $context['transfer'] = ['id' => $transfer->id, 'type' => $transfer->type->value];
        }

        return $context;
    }

    /**
     * The organization an entry belongs to: the event's organization, or the
     * one its team, invitation, role, assignment or impersonation is in, or
     * the subject's. Users belong to no one organization, so an entry about a
     * user alone is scoped to their current one.
     *
     * @param  array<string, Model>  $models
     */
    private function organization(array $models, ?Model $subject): ?OrganizationModel
    {
        foreach ($models as $model) {
            if ($model instanceof OrganizationModel) {
                return $model;
            }

            if ($model instanceof TeamModel || $model instanceof InvitationModel || $model instanceof RoleAssignmentModel
                || $model instanceof RoleModel || $model instanceof ImpersonationModel) {
                // The loaded relation when there is one, saving a query per entry.
                $loaded = $model->relationLoaded('organization') ? $model->getRelation('organization') : null;
                $organization = $loaded instanceof OrganizationModel ? $loaded : $this->find($model->getAttribute('organization_id'));

                if ($organization !== null) {
                    return $organization;
                }
            }
        }

        if ($subject === null) {
            return null;
        }

        $user = $this->userModel();

        return $user !== null && $subject instanceof $user
            ? app(Roster::class)->organization($subject)
            : $this->find($subject->getAttribute('organization_id'));
    }

    private function find(mixed $id): ?OrganizationModel
    {
        return is_int($id) || (is_string($id) && $id !== '')
            ? OrganizationModel::withTrashed()->find($id)
            : null;
    }

    /**
     * The user model Roster manages, or null while `roster.users.model` is
     * not set up yet; hooks are registered at boot, before anything is used.
     *
     * @return class-string<Model>|null
     */
    private function userModel(): ?string
    {
        try {
            return $this->users->model();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Whether the event is one of Roster's own, not another package's.
     */
    private function ownsEvent(object $event): bool
    {
        return $this->packages->for($event)?->key === 'roster';
    }
}
