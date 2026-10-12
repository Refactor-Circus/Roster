<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentityLinkedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentityLinkingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;

final class LinkSsoIdentityAction
{
    /**
     * Link the identity a signed-in user just proved at their identity
     * provider to their account.
     */
    public function execute(Model $user, SsoConnectionModel $connection, IdentityClaims $claims): SsoIdentityModel
    {
        $taken = SsoIdentityModel::query()
            ->where('connection_id', $connection->getKey())
            ->where('subject', $claims->subject)
            ->where('user_id', '!=', $user->getKey())
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_identity_taken')]);
        }

        SsoIdentityLinkingActionEvent::dispatch($user, $connection);

        $identity = DB::transaction(fn (): SsoIdentityModel => SsoIdentityModel::query()->updateOrCreate(
            ['connection_id' => $connection->getKey(), 'subject' => $claims->subject],
            ['user_id' => $user->getKey(), 'email' => $claims->email],
        ));

        SsoIdentityLinkedActionEvent::dispatch($user, $connection);

        return $identity;
    }
}
