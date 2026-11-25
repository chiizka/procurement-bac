<?php

namespace App\Models;

use App\Enums\DocStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TwgReport extends Model
{
    protected $fillable = [
        'rfq_id', 'twg_no', 'twg_date', 'project_name', 'abc', 'procurement_mode',
        'philgeps_ref', 'philgeps_posted', 'criteria', 'findings', 'signatories',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'twg_date' => 'date',
            'abc' => 'decimal:2',
            'findings' => 'array',
            'signatories' => 'array',
            'status' => DocStatus::class,
        ];
    }

    public function abstractOfQuotation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AbstractOfQuotation::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(TwgSupplier::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Suppliers that actually quoted at least one item (needs suppliers.quotes loaded). */
    public function quotationsReceived(): int
    {
        return $this->suppliers
            ->filter(fn (TwgSupplier $s) => $s->quotes->isNotEmpty())
            ->count();
    }

    /** "1 (One)" style label printed on the form. */
    public function quotationsLabel(): string
    {
        $n = $this->quotationsReceived();
        $words = [
            'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
            'Eighteen', 'Nineteen', 'Twenty',
        ];

        return isset($words[$n]) ? "{$n} ({$words[$n]})" : (string) $n;
    }

    public function signatoriesIn(string $group): array
    {
        return array_values(array_filter(
            $this->signatories ?? [],
            fn ($s) => ($s['group'] ?? null) === $group
        ));
    }
}
