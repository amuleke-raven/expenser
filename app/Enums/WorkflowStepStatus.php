<?php

namespace App\Enums;

enum WorkflowStepStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            WorkflowStepStatus::Pending => 'Pending',
            WorkflowStepStatus::Approved => 'Approved',
            WorkflowStepStatus::Rejected => 'Rejected',
            WorkflowStepStatus::Skipped => 'Skipped',
        };
    }
}
