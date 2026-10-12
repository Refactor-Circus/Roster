<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Domains\User\Concerns\HasRoster;
use RefactorCircus\Roster\Support\Tables;

/**
 * A ready-made user model for apps without one of their own.
 *
 * Point `roster.users.model` and the auth provider at this class and publish
 * its migration with the `roster-users-migration` tag. Apps with their own
 * users keep them and use the HasRoster trait instead.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserModel extends Authenticatable
{
    use DispatchesModelEvents;
    use HasRoster;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * The `roster.tables.users` table, unless a subclass names its own.
     */
    public function getTable(): string
    {
        return $this->table ?? Tables::name('users');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
