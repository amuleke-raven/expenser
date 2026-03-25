<?php

namespace App\Notifications;

use App\Models\WorkflowStepAction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowActionRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly WorkflowStepAction $action) {}

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
            ->subject("Action required: {$this->action->workflowStep->name}")
            ->line("A {$this->action->workflowStep->action_type->label()} is required")
            ->action('Review Now', url('/portal/my-approvals'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'action_id' => $this->action->id,
            'step' => $this->action->workflowStep->name,
            'type' => 'action_required',
        ];
    }
}
