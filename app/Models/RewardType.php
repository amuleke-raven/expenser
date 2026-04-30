<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_fixed',
        'is_client_based',
        'fixed_amount',
        'fixed_currency_id',
        'requires_approval',
        'workflow_id',
        'allows_custom_message',
        'requires_attachments',
        'is_recurrent',
        'recurrence_frequency',
        'recurrence_start_date',
        'recurrence_end_date',
    ];

    protected function casts(): array
    {
        return [
            'is_fixed' => 'boolean',
            'is_client_based' => 'boolean',
            'fixed_amount' => 'decimal:2',
            'requires_approval' => 'boolean',
            'allows_custom_message' => 'boolean',
            'requires_attachments' => 'boolean',
            'is_recurrent' => 'boolean',
            'recurrence_frequency' => RecurrenceFrequency::class,
            'recurrence_start_date' => 'date',
            'recurrence_end_date' => 'date',
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(RewardRule::class);
    }

    public function fixedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'fixed_currency_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
