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
        $event->reward->recipients->each(function ($recipient) use ($event) {
            $recipient->update([
                'status' => RecipientStatus::Notified,
                'notified_at' => now(),
            ]);

            app(PaymentPostingService::class)->postReward($recipient);

            $recipient->user->notify(new RewardNotification($event->reward, $recipient));
        });
    }
}
