<?php

namespace App\Models;

use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    /** @use HasFactory<TicketCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sla_hours',
        'default_assignee_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sla_hours' => 'integer',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }
}
