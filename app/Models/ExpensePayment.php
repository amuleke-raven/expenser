<?php

namespace App\Models;

use Database\Factories\ExpensePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpensePayment extends Model
{
    /** @use HasFactory<ExpensePaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'processed_by_user_id',
        'payment_method_id',
        'reference',
        'report_generated_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'report_generated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Expense, $this> */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /** @return BelongsTo<User, $this> */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /** @return BelongsTo<UserPaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethod::class, 'payment_method_id');
    }
}
