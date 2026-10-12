<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * The client secret is never serialized; `client_secret` only says whether
 * one is stored.
 *
 * @mixin SsoConnectionModel
 */
final class SsoConnectionResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'protocol' => $schema->string()->enum(SsoConnectionModel::PROTOCOLS)->required(),
            'organization' => $schema->string()->nullable()->required(),
            'settings' => $schema->object()->description('The non-secret settings: issuer, tenant, client_id, metadata_url, entity_id, sso_url.')->required(),
            'client_secret' => $schema->string()->enum(['set', null])->nullable()->description('"set" when a client secret is stored.')->required(),
            'certificate' => $schema->string()->enum(['set', null])->nullable()->description('"set" when a certificate is stored.')->required(),
            'jit' => $schema->boolean()->required(),
            'enforced' => $schema->boolean()->required(),
            'enabled' => $schema->boolean()->required(),
            'sign_in_url' => $schema->string()->nullable()->required(),
            'callback_url' => $schema->string()->nullable()->required(),
            'metadata_url' => $schema->string()->nullable()->required(),
            'identities_count' => $schema->integer()->description('When counted.'),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = array_intersect_key($this->config, array_flip(['issuer', 'tenant', 'client_id', 'metadata_url', 'entity_id', 'sso_url']));
        ksort($settings);

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'protocol' => $this->protocol,
            'organization' => $this->organization?->slug,
            'settings' => (object) $settings,
            'client_secret' => isset($this->config['client_secret']) ? 'set' : null,
            'certificate' => isset($this->config['certificate']) ? 'set' : null,
            'jit' => $this->jit,
            'enforced' => $this->enforced,
            'enabled' => $this->enabled,
            'sign_in_url' => Route::has('roster.sso.start') ? route('roster.sso.start', $this->slug) : null,
            'callback_url' => Route::has('roster.sso.callback') ? route('roster.sso.callback', $this->slug) : null,
            'metadata_url' => $this->protocol === SsoConnectionModel::SAML && Route::has('roster.sso.metadata') ? route('roster.sso.metadata', $this->slug) : null,
            'identities_count' => $this->whenCounted('identities'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
