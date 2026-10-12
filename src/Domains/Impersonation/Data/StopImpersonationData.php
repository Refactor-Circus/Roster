<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * Input for StopImpersonationAction: why it ended, one of the
 * `ImpersonationModel::ENDED_*` reasons.
 */
final readonly class StopImpersonationData extends Data
{
    public function __construct(
        public string $why = ImpersonationModel::ENDED_STOPPED,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'why' => ['sometimes', Rule::in([ImpersonationModel::ENDED_STOPPED, ImpersonationModel::ENDED_EXPIRED, ImpersonationModel::ENDED_FORCED])],
        ];
    }
}
