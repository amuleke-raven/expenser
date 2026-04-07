<?php

namespace App\Models;

use Database\Factories\TicketAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    /** @use HasFactory<TicketAttachmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        $disk = config('filesystems.default');

        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->path, now()->addMinutes(30));
        }

        return Storage::disk($disk)->url($this->path);
    }
}
