<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSyncedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSyncingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;

final class SyncOrganizationsAction
{
    public const string ERROR = 'error';

    /**
     * Sync each record with SyncOrganizationAction, each in its own
     * transaction. Each record is validated on its own, so one bad record
     * doesn't reject the batch. Results come back in the same order:
     * `{source, external_id, outcome, organization?, errors?}`.
     *
     * @return array<int, array{source: mixed, external_id: mixed, outcome: string, result?: OrganizationSyncResult, errors?: array<string, array<int, string>>}>
     */
    public function execute(SyncOrganizationsData $data): array
    {
        $records = $data->records;

        OrganizationsSyncingActionEvent::dispatch(count($records));

        $results = [];
        $links = app(SyncOrganizationAction::class)->links($records);
        $unchanged = [];

        foreach ($records as $record) {
            $entry = ['source' => $record['source'] ?? null, 'external_id' => $record['external_id'] ?? null];

            try {
                $data = SyncOrganizationData::from($record);
                $key = $data->source."\0".$data->externalId;
                $link = array_key_exists($key, $links) ? $links[$key] : new Missing;

                $result = app(SyncOrganizationAction::class)->execute($data, fresh: false, link: $link, touch: false);
                // A record repeated later in the batch finds the link this one made.
                $links[$key] = $result->link;

                if ($result->outcome === OrganizationSyncResult::UNCHANGED) {
                    $unchanged[] = $result->link;
                }

                $results[] = $entry + ['outcome' => $result->outcome, 'result' => $result];
            } catch (ValidationException $exception) {
                $results[] = $entry + ['outcome' => self::ERROR, 'errors' => $exception->errors()];
            }
        }

        $this->touch($unchanged);

        $summary = array_count_values(array_column($results, 'outcome'));

        OrganizationsSyncedActionEvent::dispatch($summary);

        return $results;
    }

    /**
     * Stamp the unchanged records' links as synced, in one update.
     *
     * @param  array<int, OrganizationLinkModel>  $links
     */
    private function touch(array $links): void
    {
        if ($links === []) {
            return;
        }

        $now = now();

        DB::transaction(fn (): int => OrganizationLinkModel::query()->whereKey(array_map(fn (OrganizationLinkModel $link): mixed => $link->getKey(), $links))->update(['synced_at' => $now]));

        foreach ($links as $link) {
            $link->synced_at = $now;
            $link->syncOriginalAttribute('synced_at');
        }
    }
}
