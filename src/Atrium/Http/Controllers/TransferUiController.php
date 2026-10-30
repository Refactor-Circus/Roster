<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ListTransfersAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowImportTemplateAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\ListTransfersData;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Atrium: CSV imports and exports - start one, review an import's preview
 * and confirm or cancel it, follow progress, and download exports.
 */
final class TransferUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Transfers $transfers) {}

    public function index(Request $request): View
    {
        $filters = ListTransfersData::from($request->query());
        $organization = $this->namedOrganization($filters->organization);
        $actor = $this->actor($request);

        // Your own transfers are always visible; an organization's need
        // roster.members.view there, everyone's roster.users.view.
        $this->authorizeScreen('roster.members.view', $organization, $organization === null ? $actor : null);

        $resolved = Atrium::screens()->resolve($this->screen('roster.transfers', Screen::LIST)->tabs($this->overviewTab()), $request);

        /** @var view-string $view */
        $view = 'roster::ui.transfers.index';

        return view($view, [
            'resolved' => $resolved,
            'filters' => $filters,
            'organization' => $organization,
            // The page's own data only while it is the open tab. Each
            // transfer's organization and requester are loaded with it, so
            // its row slot asks no query per row.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => [
                    'available' => $this->transfers->available(),
                    'transfers' => app(ListTransfersAction::class)->execute($filters, ...$this->transfers->visibleTo($actor, $organization))->withQueryString(),
                    'imports' => array_filter(TransferType::cases(), fn (TransferType $type): bool => $type->isImport()),
                    'exports' => array_filter(TransferType::cases(), fn (TransferType $type): bool => ! $type->isImport()),
                ],
                default => [],
            },
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeStart($request, 'roster.users.create');
        $data = StartImportData::from($request->all());

        $transfer = app(StartImportAction::class)->execute($data, $this->actor($request) ?? abort(401));

        return redirect()->route('atrium.roster.transfers.show', $transfer->id);
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorizeStart($request, 'roster.users.view');
        $data = StartExportData::from($request->all());

        $transfer = app(StartExportAction::class)->execute($data, $this->actor($request) ?? abort(401));

        return redirect()->route('atrium.roster.transfers.show', $transfer->id);
    }

    public function show(Request $request, string $transfer): View
    {
        $model = $this->authorizeTransfer($request, $transfer);

        $model = app(ShowTransferAction::class)->execute($model);
        $resolved = Atrium::screens()->resolve($this->screen('roster.transfer', Screen::DETAIL, $model)->tabs($this->overviewTab()), $request);

        /** @var view-string $view */
        $view = 'roster::ui.transfers.show';

        return view($view, [
            'resolved' => $resolved,
            'transfer' => $model,
            'downloadable' => $this->transfers->downloadable($model),
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => [
                    'rows' => $model->lines()->paginate(50, ['*'], 'rows_page')->withQueryString(),
                    'progress' => $this->transfers->progress($model),
                    'canConfirm' => app(Authorizer::class)->check($this->actor($request), $model->type->permission(), $model->organization),
                ],
                default => [],
            },
        ]);
    }

    public function confirm(Request $request, string $transfer): RedirectResponse
    {
        $model = TransferModel::query()->whereKey($transfer)->firstOrFail();
        abort_unless($model->type->isImport(), 404);
        // Rows are applied as the confirmer: they need the permission themselves.
        $this->authorizeScreen($model->type->permission(), $model->organization);

        app(ConfirmImportAction::class)->execute($model, $this->actor($request));

        return redirect()
            ->route('atrium.roster.transfers.show', $model->id)
            ->with('status', __('roster::roster.import_confirmed'));
    }

    public function cancel(Request $request, string $transfer): RedirectResponse
    {
        $model = $this->authorizeTransfer($request, $transfer);

        app(CancelTransferAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.transfers.show', $model->id)
            ->with('status', __('roster::roster.transfer_cancelled'));
    }

    /**
     * Templates hold no data; anyone who can open Atrium may download them.
     * The type comes in the path (index page buttons) or the query (the
     * import page's picker).
     */
    public function template(Request $request, ?string $type = null): Response
    {
        // The import page's template picker submits the type as a query.
        $type = TransferType::tryFrom($type ?? $request->string('type')->toString());
        abort_unless($type !== null && $type->isImport(), 404);

        return response(app(ShowImportTemplateAction::class)->execute($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="roster-'.$type->value.'-template.csv"',
        ]);
    }

    public function download(Request $request, string $transfer): StreamedResponse
    {
        return $this->transfers->download($this->authorizeTransfer($request, $transfer));
    }

    /**
     * The type's permission, in the organization for organization-wide
     * types; before validation, so a bad type is still refused first.
     */
    private function authorizeStart(Request $request, string $fallback): void
    {
        $type = TransferType::tryFrom($request->string('type')->toString());
        $slug = $request->input('organization');
        $organization = is_string($slug) && $slug !== '' ? OrganizationModel::query()->where('slug', $slug)->first() : null;

        $this->authorizeScreen($type?->permission() ?? $fallback, $type === null ? null : Transfers::scope($type, $organization));
    }

    /**
     * Whoever started a transfer, or anyone holding its permission in its
     * organization.
     */
    private function authorizeTransfer(Request $request, string $transfer): TransferModel
    {
        $model = TransferModel::query()->whereKey($transfer)->firstOrFail();
        $this->authorizeScreen($model->type->permission(), $model->organization, $model->requester);

        return $model;
    }

    /**
     * The organization a filter names, when it names one.
     */
    private function namedOrganization(mixed $slug): ?OrganizationModel
    {
        return is_string($slug) && $slug !== '' ? OrganizationModel::query()->where('slug', $slug)->firstOrFail() : null;
    }
}
