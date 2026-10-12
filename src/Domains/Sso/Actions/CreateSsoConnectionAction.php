<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionCreatedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionCreatingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Support\Slugs;

final class CreateSsoConnectionAction
{
    use SsoConnectionRules;

    /**
     * Connect an organization to its identity provider.
     *
     * OIDC needs `issuer`, `client_id`, `client_secret`; Microsoft Entra ID
     * (`azure`) needs `tenant`, `client_id`, `client_secret`; SAML needs
     * `metadata_url`, or `entity_id`, `sso_url` and `certificate`.
     */
    public function execute(OrganizationModel $organization, CreateSsoConnectionData $data): SsoConnectionModel
    {
        $protocol = $data->protocol;
        $settings = $this->settings($protocol, $data);

        SsoConnectionCreatingActionEvent::dispatch($organization, $data);

        $connection = DB::transaction(fn (): SsoConnectionModel => SsoConnectionModel::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data->name,
            'slug' => $data->slug !== null && $data->slug !== ''
                ? $data->slug
                : Slugs::unique($organization->slug.'-'.$data->name, SsoConnectionModel::query()),
            'protocol' => $protocol,
            'config' => $settings,
            'jit' => $data->jit,
            'enforced' => $data->enforced,
            'enabled' => $data->enabled,
        ]));

        $connection->load('organization')->loadCount('identities');

        SsoConnectionCreatedActionEvent::dispatch($connection);

        return $connection;
    }
}
