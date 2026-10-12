<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Organizations from an external system:
 * `source, external_id, name, account_number, slug, domains, owner` (email).
 * Each row is a sync record; blank cells leave the field as it is.
 */
final class OrganizationsPlanner extends Planner
{
    /**
     * What prefetch() looked up for the preview's current batch: owners by
     * lowercased email, links by source and external id. Null outside one.
     *
     * @var array{owners: array<string, Model>, emails: array<string, true>, links: array<string, OrganizationLinkModel|null>}|null
     */
    private ?array $batch = null;

    public function __construct(private readonly Users $users) {}

    /**
     * The batch's owners and existing links, in a few queries.
     */
    public function prefetch(array $rows, TransferModel $transfer): void
    {
        $emails = array_values(array_filter(array_map(fn (array $values): string => strtolower($values['owner'] ?? ''), $rows), fn (string $email): bool => $email !== ''));

        $this->batch = [
            'owners' => $this->users->findManyByEmail($emails),
            'emails' => array_fill_keys($emails, true),
            'links' => app(SyncOrganizationAction::class)->links($rows),
        ];
    }

    public function plan(array $values, TransferModel $transfer, ?Model $actor): array
    {
        try {
            $record = $this->record($values);
            $key = $record->source."\0".$record->externalId;
            $link = $this->batch !== null && array_key_exists($key, $this->batch['links']) ? $this->batch['links'][$key] : new Missing;

            return $this->describe(app(SyncOrganizationAction::class)->preview($record, $link));
        } catch (ValidationException $exception) {
            return $this->outcome(self::ERROR, ...Arr::flatten($exception->errors()));
        }
    }

    /**
     * Sync the row unless it changes nothing, which leaves its link and the
     * audit log untouched. The record is built once, and the organization is
     * not reloaded afterwards.
     */
    public function apply(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $this->batch = null;
        $sync = app(SyncOrganizationAction::class);

        try {
            $record = $this->record($values);
            $outcome = $sync->preview($record);

            if ($outcome !== OrganizationSyncResult::UNCHANGED) {
                $outcome = $sync->execute($record, fresh: false)->outcome;
            }
        } catch (ValidationException $exception) {
            return $this->outcome(self::ERROR, ...Arr::flatten($exception->errors()));
        }

        return $this->describe($outcome);
    }

    /**
     * @return array{action: string, reasons: array<int, string>}
     */
    private function describe(string $outcome): array
    {
        return match ($outcome) {
            OrganizationSyncResult::CREATED => $this->outcome(self::CREATE, __('roster::roster.import_new_organization')),
            OrganizationSyncResult::UPDATED => $this->outcome(self::UPDATE, __('roster::roster.import_updates_organization')),
            default => $this->outcome(self::SKIP, __('roster::roster.import_organization_unchanged')),
        };
    }

    /**
     * The sync record for a row, validated like any sync record: blank cells
     * are left out, `domains` is a list, and the owner's email becomes their
     * user key.
     *
     * @param  array<string, string>  $values
     */
    private function record(array $values): SyncOrganizationData
    {
        $record = array_filter($values, fn (string $value): bool => $value !== '');

        if (isset($values['owner']) && $values['owner'] !== '') {
            $record['owner'] = $this->owner($values['owner']);
        }

        if (isset($values['domains']) && $values['domains'] !== '') {
            $record['domains'] = $this->list($values['domains']);
        }

        return SyncOrganizationData::from($record);
    }

    private function owner(string $email): string
    {
        $user = $this->batch !== null && isset($this->batch['emails'][strtolower($email)])
            ? ($this->batch['owners'][strtolower($email)] ?? null)
            : $this->users->findByEmail($email);

        if ($user === null) {
            throw ValidationException::withMessages(['owner' => __('roster::roster.import_unknown_owner', ['email' => $email])]);
        }

        return (string) $user->getRouteKey();
    }
}
