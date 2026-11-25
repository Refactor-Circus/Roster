<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Support\Statuses;

/**
 * How many users there are and how many hold each status, as
 * ShowUserStatsAction counts them. No model stands behind it, so it lives in
 * the domain's `Http/Resources`.
 *
 * @property array{total: int, by_status: array<string, int>} $resource
 */
final class UserStatsResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        $byStatus = [];

        foreach (Statuses::values(Statuses::users()) as $status) {
            $byStatus[$status] = $schema->integer()->description('Users with this status.')->required();
        }

        return [
            'total' => $schema->integer()->description('Every user, deleted ones left out.')->required(),
            'by_status' => $schema->object($byStatus)->description('Users per status, every configured status listed; a user without a profile counts as active.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->resource['total'],
            'by_status' => $this->resource['by_status'],
        ];
    }
}
