<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

/**
 * @mixin OrganizationModel
 */
final class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'owner' => $schema->union(['integer', 'string', 'null'])->description('The owner\'s user route key.')->required(),
            'personal' => $schema->boolean()->required(),
            'auto_join' => $schema->boolean()->required(),
            'provisioned_status' => $schema->string()->enum(array_column(UserStatus::cases(), 'value'))->required(),
            'domains' => $schema->array()->items($schema->string())->description('Email domains, when loaded.'),
            'links' => $schema->array()->items($schema->object([
                'source' => $schema->string()->required(),
                'external_id' => $schema->string()->required(),
                'account_number' => $schema->string()->nullable()->required(),
                'synced_at' => $schema->string()->nullable()->required(),
            ]))->description('Its records in external systems, one per source.')->required(),
            'members_count' => $schema->integer()->description('When counted.'),
            'teams_count' => $schema->integer()->description('When counted.'),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $owner = $this->owner;

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'owner' => $owner instanceof Model ? $owner->getRouteKey() : null,
            'personal' => $this->personal,
            'auto_join' => $this->auto_join,
            'provisioned_status' => $this->provisioned_status,
            'domains' => $this->whenLoaded('domains', fn (): array => $this->domains
                ->map(fn (OrganizationDomainModel $domain): string => $domain->domain)
                ->sort()
                ->values()
                ->all()),
            // Its records in external systems (ERP, CRM, ...), one per source.
            'links' => $this->links
                ->sortBy('source')
                ->map(fn (OrganizationLinkModel $link): array => [
                    'source' => $link->source,
                    'external_id' => $link->external_id,
                    'account_number' => $link->account_number,
                    'synced_at' => $link->synced_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'members_count' => $this->whenCounted('memberships'),
            'teams_count' => $this->whenCounted('teams'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
