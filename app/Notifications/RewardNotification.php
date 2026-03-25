<?php

namespace App\Notifications;

use App\Models\Reward;
use App\Models\RewardRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RewardNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reward $reward,
        public readonly RewardRecipient $recipient,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have received a reward')
            ->line("Type: {$this->reward->rewardType->name}")
            ->line("Amount: {$this->reward->currency->symbol}{$this->reward->amount}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reward_id' => $this->reward->id,
            'ref' => $this->reward->ref(),
            'type' => 'reward_received',
        ];
    }
}
