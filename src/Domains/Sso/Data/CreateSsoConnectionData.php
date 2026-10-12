<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * Input for CreateSsoConnectionAction: the connection and its protocol's
 * settings.
 *
 * OIDC needs `issuer`, `client_id`, `client_secret`; Microsoft Entra ID
 * (`azure`) needs `tenant`, `client_id`, `client_secret`; SAML needs
 * `metadata_url`, or `entity_id`, `sso_url` and `certificate`. Without
 * `slug` one is made from the organization's slug and the name.
 */
final readonly class CreateSsoConnectionData extends Data
{
    use SsoConnectionRules;

    public function __construct(
        public string $name,
        public string $protocol,
        public ?string $slug = null,
        public ?string $issuer = null,
        public ?string $tenant = null,
        public ?string $clientId = null,
        public ?string $clientSecret = null,
        public ?string $metadataUrl = null,
        public ?string $entityId = null,
        public ?string $ssoUrl = null,
        public ?string $certificate = null,
        public bool $jit = true,
        public bool $enforced = false,
        public bool $enabled = true,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', Rule::unique(SsoConnectionModel::class, 'slug')],
            'protocol' => ['required', Rule::in(SsoConnectionModel::PROTOCOLS)],
        ] + self::settingRules();
    }
}
