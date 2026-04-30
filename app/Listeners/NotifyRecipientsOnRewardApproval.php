<?php

namespace App\Listeners;

use App\Enums\RecipientStatus;
use App\Events\RewardApproved;
use App\Notifications\RewardNotification;
use App\Services\PaymentPostingService;

class NotifyRecipientsOnRewardApproval
{
    public function handle(RewardApproved $event): void
    {
        // Eager load the user relationship to ensure it's available
        $event->reward->load('recipients.user');

        $event->reward->recipients->each(function ($recipient) use ($event) {
            $recipient->update([
                'status' => RecipientStatus::Notified,
                'notified_at' => now(),
            ]);

            app(PaymentPostingService::class)->postReward($recipient);

            // Only send notification if recipient has a user account (internal recipient)
            if ($recipient->user) {
                $recipient->user->notify(new RewardNotification($event->reward, $recipient));
            }
        });
    }
}
