<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for SyncOrganizationAction: one record from an external system,
 * matched by `source` + `external_id`. Fields left out are kept.
 *
 * On the first sync, `organization` (a slug) links an existing organization
 * instead of creating one. `owner` is a user route key.
 */
final readonly class SyncOrganizationData extends Data
{
    /**
     * @param  array<int, string>|Missing  $domains
     */
    public function __construct(
        public string $source,
        public string $externalId,
        public string|Missing|null $accountNumber = new Missing,
        public string|Missing $name = new Missing,
        public string|Missing|null $slug = new Missing,
        public array|Missing $domains = new Missing,
        public bool|Missing $autoJoin = new Missing,
        public int|string|null $owner = null,
        public ?string $organization = null,
    ) {}

    /**
     * The record's shape. Uniqueness (slug, domains) is checked against the
     * organization the record resolves to, in the Action.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_.-]*$/'],
            'external_id' => ['required', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255'],
            'domains' => ['sometimes', 'array', 'max:50'],
            'domains.*' => ['string', 'distinct', 'max:253'],
            'auto_join' => ['sometimes', 'boolean'],
            'owner' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
