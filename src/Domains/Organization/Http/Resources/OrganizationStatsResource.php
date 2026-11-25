<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * How many organizations, teams and pending invitations there are, as
 * ShowOrganizationStatsAction counts them. No model stands behind it, so it
 * lives in the domain's `Http/Resources`.
 *
 * @property array{organizations: int, teams: int, pending_invitations: int} $resource
 */
final class OrganizationStatsResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'organizations' => $schema->integer()->description('Organizations, deleted ones left out.')->required(),
            'teams' => $schema->integer()->description('Teams.')->required(),
            'pending_invitations' => $schema->integer()->description('Invitations not yet accepted, declined, revoked or expired.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'organizations' => $this->resource['organizations'],
            'teams' => $this->resource['teams'],
            'pending_invitations' => $this->resource['pending_invitations'],
        ];
    }
}
