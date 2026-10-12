<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Support\Tables;

/**
 * One row of an import: its values, what the preview planned, and what
 * applying it did.
 *
 * @property int $id
 * @property string $transfer_id
 * @property int $line
 * @property array<string, string> $values
 * @property string $action
 * @property array<int, string> $reasons
 * @property string|null $result
 * @property array<int, string>|null $result_reasons
 */
final class TransferRowModel extends Model
{
    use DispatchesModelEvents;

    public $timestamps = false;

    protected $fillable = ['transfer_id', 'line', 'values', 'action', 'reasons', 'result', 'result_reasons'];

    public function getTable(): string
    {
        return Tables::name('transfer_rows');
    }

    /**
     * @return BelongsTo<TransferModel, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(TransferModel::class, 'transfer_id');
    }

    /**
     * The row as the API and report show it.
     *
     * @return array<string, mixed>
     */
    public function toReport(): array
    {
        return array_filter([
            'line' => $this->line,
            'values' => $this->values,
            'action' => $this->action,
            'reasons' => $this->reasons,
            'result' => $this->result,
            'result_reasons' => $this->result_reasons,
        ], fn (mixed $value): bool => $value !== null);
    }

    protected function casts(): array
    {
        return [
            'line' => 'integer',
            'values' => 'array',
            'reasons' => 'array',
            'result_reasons' => 'array',
        ];
    }
}
