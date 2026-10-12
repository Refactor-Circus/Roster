<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for AddTeamMemberAction: the organization member to seat, by route
 * key or as the model itself.
 */
final readonly class AddTeamMemberData extends Data
{
    public function __construct(
        public Model|int|string $user,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required'],
        ];
    }
}
