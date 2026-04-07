<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'category_id',
        'requester_id',
        'assignee_id',
        'title',
        'description',
        'status',
        'priority',
        'first_response_at',
        'resolved_at',
        'due_at',
        'sla_breached',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['is_overdue', 'sla_countdown_hours'];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'due_at' => 'datetime',
            'sla_breached' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
                $ticket->ticket_number = 'RR-'.$year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            }

            if (empty($ticket->due_at) && $ticket->category_id) {
                $category = TicketCategory::find($ticket->category_id);
                if ($category) {
                    $multiplier = $ticket->priority instanceof TicketPriority
                        ? $ticket->priority->slaMultiplier()
                        : TicketPriority::Medium->slaMultiplier();
                    $hours = (int) round($category->sla_hours * $multiplier);
                    $ticket->due_at = now()->addHours($hours);
                }
            }
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activityLog(): HasMany
    {
        return $this->hasMany(TicketActivityLog::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Open);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assignee_id');
    }

    public function scopeBreachingSlA(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')
            ->whereNotIn('status', [
                TicketStatus::Resolved->value,
                TicketStatus::Closed->value,
                TicketStatus::Cancelled->value,
            ])
            ->where('due_at', '<', now()->addHours(2));
    }

    public function scopeForRequester(Builder $query, User $user): Builder
    {
        return $query->where('requester_id', $user->id);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed, TicketStatus::Cancelled]);
    }

    public function getSlaCountdownHoursAttribute(): int
    {
        if ($this->due_at === null) {
            return 0;
        }

        return (int) max(0, now()->diffInHours($this->due_at, false));
    }
}
