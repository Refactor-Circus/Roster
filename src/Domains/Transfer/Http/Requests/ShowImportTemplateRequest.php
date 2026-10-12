<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowImportTemplateAction;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Templates hold no data - just columns and commented examples - so any
 * signed-in user may download them.
 */
final class ShowImportTemplateRequest extends Request
{
    public function authorize(): bool
    {
        return ! app(Authorizer::class)->enabled() || $this->actor() !== null;
    }

    protected function ability(): string
    {
        return '';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->route('type')]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(
                fn (TransferType $type): string => $type->value,
                array_filter(TransferType::cases(), fn (TransferType $type): bool => $type->isImport()),
            ))],
        ];
    }

    public function persist(): Response
    {
        $type = TransferType::from((string) $this->validated('type'));

        return response(app(ShowImportTemplateAction::class)->execute($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="roster-'.$type->value.'-template.csv"',
        ]);
    }
}
