<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * Validation and storage of a connection's protocol settings.
 */
trait SsoConnectionRules
{
    /** @var array<string, array<int, string>> */
    private const array SETTINGS = [
        SsoConnectionModel::OIDC => ['issuer', 'client_id', 'client_secret'],
        SsoConnectionModel::AZURE => ['tenant', 'client_id', 'client_secret'],
        SsoConnectionModel::SAML => ['metadata_url', 'entity_id', 'sso_url', 'certificate'],
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function settingRules(): array
    {
        return [
            'issuer' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'tenant' => ['sometimes', 'nullable', 'string', 'max:255', Rule::notIn(['common', 'organizations', 'consumers'])],
            'client_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'client_secret' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'metadata_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'entity_id' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'sso_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'certificate' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'jit' => ['sometimes', 'boolean'],
            'enforced' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The protocol's settings from input over the current ones. A blank
     * client secret keeps the stored one.
     *
     * @param  array<string, mixed>  $current
     * @return array<string, string>
     */
    private function settings(string $protocol, CreateSsoConnectionData|UpdateSsoConnectionData $data, array $current = []): array
    {
        $settings = [];

        foreach (self::SETTINGS[$protocol] ?? [] as $key) {
            $given = self::given($data, $key);
            $value = ! $given instanceof Missing && ! ($key === 'client_secret' && blank($given))
                ? $given
                : ($current[$key] ?? null);

            if (is_string($value) && $value !== '') {
                $settings[$key] = trim($value);
            }
        }

        $this->guardSettings($protocol, $settings);

        return $settings;
    }

    /**
     * The input's value for a setting; Missing when it was left out.
     */
    private static function given(CreateSsoConnectionData|UpdateSsoConnectionData $data, string $key): string|Missing|null
    {
        return match ($key) {
            'issuer' => $data->issuer,
            'tenant' => $data->tenant,
            'client_id' => $data->clientId,
            'client_secret' => $data->clientSecret,
            'metadata_url' => $data->metadataUrl,
            'entity_id' => $data->entityId,
            'sso_url' => $data->ssoUrl,
            'certificate' => $data->certificate,
            default => new Missing,
        };
    }

    /**
     * @param  array<string, string>  $settings
     */
    private function guardSettings(string $protocol, array $settings): void
    {
        $required = match ($protocol) {
            SsoConnectionModel::OIDC => ['issuer', 'client_id', 'client_secret'],
            SsoConnectionModel::AZURE => ['tenant', 'client_id', 'client_secret'],
            default => isset($settings['metadata_url']) ? ['metadata_url'] : ['entity_id', 'sso_url', 'certificate'],
        };

        foreach ($required as $key) {
            if (! isset($settings[$key])) {
                throw ValidationException::withMessages([$key => __('roster::roster.sso_setting_required', ['setting' => $key])]);
            }
        }

        // Identity providers must be reached over HTTPS, outside local work.
        foreach (['issuer', 'metadata_url', 'sso_url'] as $key) {
            if (isset($settings[$key]) && ! str_starts_with($settings[$key], 'https://') && ! app()->environment('local', 'testing')) {
                throw ValidationException::withMessages([$key => __('roster::roster.sso_https_required')]);
            }
        }
    }
}
