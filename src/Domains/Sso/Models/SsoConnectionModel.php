<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\SsoConnectionFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * An organization's identity provider: OIDC, SAML or Microsoft Entra ID.
 *
 * `config` is encrypted at rest. For OIDC it holds `issuer`, `client_id`
 * and `client_secret`; for Microsoft Entra ID (`azure`) `tenant`,
 * `client_id` and `client_secret`; for SAML either `metadata_url`, or
 * `entity_id`, `sso_url` and `certificate`.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $slug
 * @property string $protocol
 * @property array<string, mixed> $config
 * @property bool $jit
 * @property bool $enforced
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SsoConnectionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<SsoConnectionFactory> */
    use HasFactory;

    use HasUlids;

    public const string OIDC = 'oidc';

    public const string SAML = 'saml';

    public const string AZURE = 'azure';

    /** @var array<int, string> */
    public const array PROTOCOLS = [self::OIDC, self::SAML, self::AZURE];

    protected $fillable = ['organization_id', 'name', 'slug', 'protocol', 'config', 'jit', 'enforced', 'enabled'];

    protected $hidden = ['config'];

    protected $attributes = [
        'jit' => true,
        'enforced' => false,
        'enabled' => true,
    ];

    public function getTable(): string
    {
        return Tables::name('sso_connections');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return HasMany<SsoIdentityModel, $this>
     */
    public function identities(): HasMany
    {
        return $this->hasMany(SsoIdentityModel::class, 'connection_id');
    }

    public function setting(string $key): ?string
    {
        $value = $this->config[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Whether the connection's organization owns the email's domain, which
     * makes its identity provider authoritative for that address.
     */
    public function trusts(string $email): bool
    {
        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        return str_contains($email, '@')
            && $this->organization()->first()?->domains()->where('domain', $domain)->exists() === true;
    }

    /**
     * A deleted organization's connections can't be used or managed until
     * it's restored.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('live_organization', fn (Builder $query): Builder => $query->whereHas('organization'));
    }

    protected static function newFactory(): SsoConnectionFactory
    {
        return SsoConnectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'jit' => 'boolean',
            'enforced' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
