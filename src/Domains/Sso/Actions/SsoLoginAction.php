<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Roster\Domains\Sso\Events\SsoLoginFailedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoLoginStartingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoLoginSucceededActionEvent;
use RefactorCircus\Roster\Domains\Sso\Exceptions\SsoLoginRefused;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;

final class SsoLoginAction
{
    use ManagesMemberships;

    public const string BY_IDENTITY = 'identity';

    public const string BY_LINK = 'linked';

    public const string BY_JIT = 'jit';

    public function __construct(private readonly Users $users) {}

    /**
     * Find, link or create the account an identity provider vouched for.
     *
     * In order: an identity already linked to this subject; else an account
     * with the same email, linked automatically only when the organization
     * owns the email's domain; else a new account (just-in-time), under the
     * same domain rule. The user ends up a member of the organization.
     *
     * @return array{0: Model, 1: string}
     */
    public function execute(SsoConnectionModel $connection, IdentityClaims $claims): array
    {
        SsoLoginStartingActionEvent::dispatch($connection);

        if (! $connection->enabled) {
            SsoLoginFailedActionEvent::dispatch($connection, $claims->email, 'disabled');

            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_failed_disabled')]);
        }

        try {
            [$user, $method] = $this->signIn($connection, $claims);

            // After the transaction: an account waiting for approval (just
            // created, or earlier) is kept, linked and a member - only the
            // sign-in is refused.
            if ($this->users->status($user) === UserStatus::Pending) {
                $this->fail($connection, $claims, 'pending');
            }
        } catch (SsoLoginRefused $refused) {
            // Recorded outside the transaction, so the rollback cannot erase it.
            SsoLoginFailedActionEvent::dispatch($connection, $claims->email, $refused->reason);

            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_failed_'.$refused->reason)]);
        }

        SsoLoginSucceededActionEvent::dispatch($user, $connection, $method);

        return [$user, $method];
    }

    /**
     * @return array{0: Model, 1: string}
     */
    private function signIn(SsoConnectionModel $connection, IdentityClaims $claims): array
    {
        return DB::transaction(function () use ($connection, $claims): array {
            $identity = SsoIdentityModel::query()
                ->where('connection_id', $connection->getKey())
                ->where('subject', $claims->subject)
                ->first();

            if ($identity !== null && $identity->user instanceof Model) {
                [$user, $method] = [$identity->user, self::BY_IDENTITY];
            } else {
                [$user, $method] = $this->linkOrCreate($connection, $claims);
            }

            if (! in_array($this->users->status($user), [UserStatus::Active, UserStatus::Pending], true)) {
                $this->fail($connection, $claims, 'inactive');
            }

            /** @var OrganizationModel $organization */
            $organization = $connection->organization;
            $this->join($organization, $user, MembershipSource::Domain);

            SsoIdentityModel::query()->updateOrCreate(
                ['connection_id' => $connection->getKey(), 'subject' => $claims->subject],
                ['user_id' => $user->getKey(), 'email' => $claims->email, 'last_login_at' => now()],
            );

            return [$user, $method];
        });
    }

    /**
     * @return array{0: Model, 1: string}
     */
    private function linkOrCreate(SsoConnectionModel $connection, IdentityClaims $claims): array
    {
        $email = $claims->email;

        if ($email === null || ! $connection->trusts($email)) {
            $this->fail($connection, $claims, 'untrusted_email');
        }

        $existing = $this->findByEmail($email);

        if ($existing !== null) {
            return [$existing, self::BY_LINK];
        }

        if (! $connection->jit) {
            $this->fail($connection, $claims, 'no_account');
        }

        /** @var OrganizationModel $organization */
        $organization = $connection->organization;

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            email: $email,
            name: $claims->name ?? Str::before($email, '@'),
            // The organization decides whether its new accounts need approval.
            status: UserStatus::from($organization->provisioned_status),
        ));

        // The identity provider is authoritative for this domain, so the
        // address counts as verified (where the users table tracks that).
        if ($this->hasVerifiedColumn($user) && $user->getAttribute('email_verified_at') === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return [$user, self::BY_JIT];
    }

    private function hasVerifiedColumn(Model $user): bool
    {
        return $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'email_verified_at');
    }

    private function findByEmail(string $email): ?Model
    {
        return $this->users->findByEmail($email);
    }

    private function fail(SsoConnectionModel $connection, IdentityClaims $claims, string $reason): never
    {
        throw new SsoLoginRefused($reason);
    }
}
