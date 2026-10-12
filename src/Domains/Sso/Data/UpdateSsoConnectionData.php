<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * Input for UpdateSsoConnectionAction: the fields and protocol settings to
 * change. Fields left out are kept, and so is the stored client secret when
 * the given one is blank. The protocol is fixed.
 */
final readonly class UpdateSsoConnectionData extends Data
{
    use SsoConnectionRules;

    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $slug = new Missing,
        public string|Missing|null $issuer = new Missing,
        public string|Missing|null $tenant = new Missing,
        public string|Missing|null $clientId = new Missing,
        public string|Missing|null $clientSecret = new Missing,
        public string|Missing|null $metadataUrl = new Missing,
        public string|Missing|null $entityId = new Missing,
        public string|Missing|null $ssoUrl = new Missing,
        public string|Missing|null $certificate = new Missing,
        public bool|Missing $jit = new Missing,
        public bool|Missing $enforced = new Missing,
        public bool|Missing $enabled = new Missing,
    ) {}

    /**
     * Pass the connection being updated so its own slug passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?SsoConnectionModel $connection = null): array
    {
        $slug = Rule::unique(SsoConnectionModel::class, 'slug');

        if ($connection !== null) {
            $slug->ignore($connection->getKey());
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ] + self::settingRules();
    }
}
