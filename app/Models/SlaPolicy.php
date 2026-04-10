<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'priority',
        'response_hours',
        'resolve_hours',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
        ];
    }
}
