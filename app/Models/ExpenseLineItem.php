<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:4',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $lineItem) {
            $lineItem->total = $lineItem->quantity * $lineItem->unit_price;
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
