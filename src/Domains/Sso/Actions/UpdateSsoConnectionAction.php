<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

final class UpdateSsoConnectionAction
{
    use SsoConnectionRules;

    /**
     * Change a connection. The protocol is fixed; a blank client secret
     * keeps the stored one.
     */
    public function execute(SsoConnectionModel $connection, UpdateSsoConnectionData $data): SsoConnectionModel
    {
        $settings = $this->settings($connection->protocol, $data, $connection->config);

        SsoConnectionUpdatingActionEvent::dispatch($connection, $data);

        DB::transaction(fn () => $connection->update(
            Arr::only($data->toArray(), ['name', 'slug', 'jit', 'enforced', 'enabled']) + ['config' => $settings],
        ));

        $connection = $connection->refresh()->load('organization')->loadCount('identities');

        SsoConnectionUpdatedActionEvent::dispatch($connection);

        return $connection;
    }
}
