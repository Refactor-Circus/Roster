<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationSyncResults;
use RefactorCircus\Roster\Http\Request;

/**
 * Sync a batch of external records; each record succeeds or fails alone.
 */
final class SyncOrganizationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    public function rules(): array
    {
        return SyncOrganizationsData::rules();
    }

    public function persist(): JsonResponse
    {
        return response()->json(['data' => OrganizationSyncResults::present(app(SyncOrganizationsAction::class)->execute(SyncOrganizationsData::fromValidated($this->validated())))]);
    }
}
