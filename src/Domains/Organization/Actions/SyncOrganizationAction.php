<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationSyncedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationSyncingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class SyncOrganizationAction
{
    use ManagesMemberships;

    /**
     * The organization fields a record may set; anything left out is kept.
     */
    private const array FIELDS = ['name', 'slug', 'domains', 'auto_join', 'status', 'status_reason'];

    public function __construct(private readonly Users $users) {}

    /**
     * Create or update the organization behind an external record, matched
     * by `source` + `external_id`. On the first sync, `organization` (a slug)
     * links an existing organization instead of creating one.
     *
     * Only the fields present are written. `owner` (a user route key) is
     * applied when creating, or when the organization has no owner yet; an
     * existing owner is never replaced. Slug and domain uniqueness are
     * checked here, against the organization the record resolves to.
     *
     * `$fresh: false` skips reloading the organization's domains, links and
     * counts afterwards, for callers that only need the outcome (bulk sync).
     * Bulk callers may also pass the record's `$link` they already looked up
     * (null when there is none; its organization and domains loaded), and
     * `$touch: false` to stamp `synced_at` on unchanged links themselves in
     * one update: such a link comes back with `synced_at` unsaved.
     */
    public function execute(SyncOrganizationData $data, bool $fresh = true, OrganizationLinkModel|Missing|null $link = new Missing, bool $touch = true): OrganizationSyncResult
    {
        $source = $data->source;
        $externalId = $data->externalId;

        OrganizationSyncingActionEvent::dispatch($data);

        $result = DB::transaction(function () use ($data, $source, $externalId, $fresh, $link, $touch): OrganizationSyncResult {
            if ($link instanceof Missing) {
                $link = $this->link($source, $externalId);
            }

            $organization = $link !== null ? $link->organization : $this->existing($data);

            [$organization, $outcome] = $organization === null
                ? [$this->create($data), OrganizationSyncResult::CREATED]
                : [$organization, $this->update($organization, $data) ? OrganizationSyncResult::UPDATED : OrganizationSyncResult::UNCHANGED];

            $link ??= new OrganizationLinkModel(['organization_id' => $organization->getKey(), 'source' => $source, 'external_id' => $externalId]);

            if (! $data->accountNumber instanceof Missing) {
                $link->account_number = $data->accountNumber;
            }

            if ($outcome === OrganizationSyncResult::UNCHANGED && (! $link->exists || $link->isDirty('account_number'))) {
                $outcome = OrganizationSyncResult::UPDATED;
            }

            $link->synced_at = now();

            // Unchanged means the link exists and only its stamp moves.
            if ($touch || $outcome !== OrganizationSyncResult::UNCHANGED) {
                $link->save();
            }

            if ($fresh) {
                $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);
            }

            return new OrganizationSyncResult($organization, $link, $outcome);
        });

        OrganizationSyncedActionEvent::dispatch($result->organization, $source, $externalId, $result->link->account_number, $result->outcome);

        return $result;
    }

    /**
     * What `execute()` would do with the record - created, updated or
     * unchanged - without writing anything or firing events. Throws the same
     * ValidationException it would. Used by the CSV import's preview.
     */
    public function preview(SyncOrganizationData $data, OrganizationLinkModel|Missing|null $link = new Missing): string
    {
        if ($link instanceof Missing) {
            $link = $this->link($data->source, $data->externalId);
        }

        $organization = $link !== null ? $link->organization : $this->existing($data);

        if ($organization === null) {
            $this->creation($data);

            return OrganizationSyncResult::CREATED;
        }

        $changed = $this->changes($organization, $data);

        if ($changed !== []) {
            UpdateOrganizationData::from($changed, $organization);
        }

        $claims = $data->owner !== null && $data->owner !== '' && $organization->owner_id === null;
        $account = ! $data->accountNumber instanceof Missing && $data->accountNumber !== $link?->account_number;

        return $changed !== [] || $claims || $link === null || $account
            ? OrganizationSyncResult::UPDATED
            : OrganizationSyncResult::UNCHANGED;
    }

    /**
     * The existing link for every record, with its organization and domains,
     * keyed by source and external id: null where a record has none yet.
     * For bulk callers, to hand each record's entry to execute() or preview().
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, OrganizationLinkModel|null>
     */
    public function links(array $records): array
    {
        $wanted = [];

        foreach ($records as $record) {
            $source = $record['source'] ?? null;
            $external = $record['external_id'] ?? null;

            if (is_string($source) && (is_string($external) || is_int($external))) {
                $wanted[$source."\0".$external] = [$source, (string) $external];
            }
        }

        $links = array_fill_keys(array_keys($wanted), null);

        foreach (array_chunk($wanted, 500) as $chunk) {
            OrganizationLinkModel::query()
                ->with('organization.domains')
                ->whereIn('source', array_unique(array_column($chunk, 0)))
                ->whereIn('external_id', array_unique(array_column($chunk, 1)))
                ->get()
                ->each(function (OrganizationLinkModel $link) use (&$links): void {
                    $key = $link->source."\0".$link->external_id;

                    if (array_key_exists($key, $links)) {
                        $links[$key] = $link;
                    }
                });
        }

        return $links;
    }

    private function link(string $source, string $externalId): ?OrganizationLinkModel
    {
        return OrganizationLinkModel::query()->where('source', $source)->where('external_id', $externalId)->first();
    }

    /**
     * The organization named by `organization` on a first sync, if any.
     */
    private function existing(SyncOrganizationData $data): ?OrganizationModel
    {
        $slug = $data->organization;
        $source = $data->source;

        if ($slug === null || $slug === '') {
            return null;
        }

        $organization = OrganizationModel::query()->where('slug', $slug)->first()
            ?? throw ValidationException::withMessages(['organization' => __('roster::roster.unknown_organization')]);

        if ($organization->links()->where('source', $source)->exists()) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.already_linked', ['source' => $source])]);
        }

        return $organization;
    }

    private function create(SyncOrganizationData $data): OrganizationModel
    {
        return app(CreateOrganizationAction::class)->execute($this->creation($data));
    }

    /**
     * The record's fields as a new organization, checked as
     * CreateOrganizationAction's input (a name is required).
     */
    private function creation(SyncOrganizationData $data): CreateOrganizationData
    {
        return CreateOrganizationData::from(array_intersect_key($data->toArray(), array_flip([...self::FIELDS, 'owner'])));
    }

    /**
     * Write the record's fields where they differ; true when anything changed.
     */
    private function update(OrganizationModel $organization, SyncOrganizationData $data): bool
    {
        $changed = $this->changes($organization, $data);

        if ($changed !== []) {
            app(UpdateOrganizationAction::class)->execute($organization, UpdateOrganizationData::from($changed, $organization));
        }

        return $this->claimOwnership($organization, $data->owner) || $changed !== [];
    }

    /**
     * The record's fields that differ from the organization, under their
     * column names.
     *
     * @return array<string, mixed>
     */
    private function changes(OrganizationModel $organization, SyncOrganizationData $data): array
    {
        $fields = array_intersect_key($data->toArray(), array_flip(self::FIELDS));

        return array_filter($fields, fn (mixed $value, string $field): bool => $this->differs($organization, $field, $value), ARRAY_FILTER_USE_BOTH);
    }

    private function differs(OrganizationModel $organization, string $field, mixed $value): bool
    {
        return match ($field) {
            'domains' => $this->normalized((array) $value) !== $this->normalized($organization->loadMissing('domains')->domains->pluck('domain')->all()),
            'auto_join' => (bool) $value !== $organization->auto_join,
            'status' => $value !== $organization->status,
            'slug' => $value !== null && $value !== '' && $value !== $organization->slug,
            default => (string) $value !== (string) $organization->getAttribute($field),
        };
    }

    /**
     * @param  array<int, mixed>  $domains
     * @return array<int, string>
     */
    private function normalized(array $domains): array
    {
        $domains = array_values(array_unique(array_map(fn (mixed $domain): string => strtolower((string) $domain), $domains)));
        sort($domains);

        return $domains;
    }

    /**
     * Give an ownerless organization the record's owner, who joins it.
     */
    private function claimOwnership(OrganizationModel $organization, int|string|null $owner): bool
    {
        if ($owner === null || $owner === '' || $organization->owner_id !== null) {
            return false;
        }

        $user = $this->users->query()->where($this->users->routeKeyName(), $owner)->first()
            ?? throw ValidationException::withMessages(['owner' => __('validation.exists', ['attribute' => 'owner'])]);

        $organization->update(['owner_id' => $user->getKey()]);
        $this->join($organization, $user, MembershipSource::Direct);

        return true;
    }
}
