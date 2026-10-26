<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Throwable;

/**
 * Which registered tabs this request's viewer may see, and which tab keys
 * have already reported a failing authorize closure.
 *
 * Bound with `scoped()`, so it is rebuilt for every request (and every
 * Octane request or queued job): an answer for one viewer never reaches
 * another. It never lives on Tabs or TabDefinition, which last as long as
 * the application.
 */
final class TabVisibility
{
    /**
     * Answers by screen, record key and tab key.
     *
     * @var array<string, array<array-key, array<array-key, bool>>>
     */
    private array $answers = [];

    /**
     * Tab keys whose authorize closure has been reported this request.
     *
     * @var array<array-key, true>
     */
    private array $reported = [];

    /**
     * Whether the viewer may see the tab on this screen's record, asked once
     * per request.
     *
     * @param  'organization'|'user'  $screen
     */
    public function allows(string $screen, TabDefinition $tab, Request $request, Model $record, ?OrganizationModel $scope, ?Model $self): bool
    {
        $recordKey = $record->getKey();
        $recordKey = is_int($recordKey) || is_string($recordKey) ? (string) $recordKey : '';

        return $this->answers[$screen][$recordKey][$tab->key] ??= $tab->allows($request, $record, $scope, $self);
    }

    /**
     * Report a tab's failing authorize closure, once per request per key.
     */
    public function report(string $key, Throwable $exception): void
    {
        if (isset($this->reported[$key])) {
            return;
        }

        $this->reported[$key] = true;

        report($exception);
    }
}
