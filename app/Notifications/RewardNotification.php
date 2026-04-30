<?php

namespace App\Notifications;

use App\Models\Reward;
use App\Models\RewardRecipient;
use Filament\Notifications\Notification as FilamentNotification;
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
        $message = (new MailMessage)
            ->subject('You have received a disbursement')
            ->line("Type: {$this->reward->rewardType->name}")
            ->line("Amount: {$this->reward->currency->symbol}{$this->reward->amount}");

        if ($this->reward->custom_message) {
            $message->line('')
                ->line($this->reward->custom_message);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Build notification body
        $body = "**Disbursement:** {$this->reward->ref()}\n\n";
        $body .= "**Type:** {$this->reward->rewardType->name}\n\n";
        $body .= "**Amount:** {$this->reward->currency->symbol}{$this->reward->amount}";

        // Add custom message if present
        if ($this->reward->custom_message) {
            $body .= "\n\n---\n\n";
            $body .= $this->reward->custom_message;
        }

        return FilamentNotification::make()
            ->title('You have received a disbursement')
            ->icon('heroicon-o-gift')
            ->iconColor('success')
            ->body($body)
            ->getDatabaseMessage();
    }
}
