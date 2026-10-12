<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * A user's account at an identity provider, matched by its subject.
 *
 * @property string $id
 * @property string $connection_id
 * @property int|string $user_id
 * @property string $subject
 * @property string|null $email
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SsoIdentityModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['connection_id', 'user_id', 'subject', 'email', 'last_login_at'];

    public function getTable(): string
    {
        return Tables::name('sso_identities');
    }

    /**
     * @return BelongsTo<SsoConnectionModel, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(SsoConnectionModel::class, 'connection_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime'];
    }

    /**
     * Whether the sign-in is the user's own.
     */
    public function ownedBy(?Model $user): bool
    {
        return $user !== null && (string) $this->user_id === (string) $user->getKey();
    }
}
