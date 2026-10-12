<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\TransferFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * A CSV import or export: its files, its Impex run, and its report.
 *
 * @property string $id
 * @property TransferType $type
 * @property string|null $organization_id
 * @property int|string $requested_by
 * @property TransferStatus $status
 * @property string|null $impex_run_id
 * @property string|null $input_path
 * @property string|null $output_path
 * @property array<string, mixed>|null $filters
 * @property array<string, mixed>|null $report
 * @property int $row_count
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TransferModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'type', 'organization_id', 'requested_by', 'status', 'impex_run_id', 'input_path', 'output_path',
        'filters', 'report', 'row_count', 'confirmed_at', 'finished_at', 'expires_at',
    ];

    public function getTable(): string
    {
        return Tables::name('transfers');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'requested_by');
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        /** @var array<string, int> */
        return (array) ($this->report['summary'] ?? []);
    }

    /**
     * @return HasMany<TransferRowModel, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TransferRowModel::class, 'transfer_id')->orderBy('line');
    }

    /**
     * Every row's plan and result. For large imports, page through `lines()`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->lines()->get()->map(fn (TransferRowModel $row): array => $row->toReport())->all();
    }

    /**
     * Every column but the report, which can be large.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withoutReport(Builder $query): void
    {
        $query->select(array_values(array_diff(['id', ...$this->getFillable(), 'created_at', 'updated_at'], ['report'])));
    }

    protected static function newFactory(): TransferFactory
    {
        return TransferFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => TransferType::class,
            'status' => TransferStatus::class,
            'filters' => 'array',
            'report' => 'array',
            'confirmed_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
