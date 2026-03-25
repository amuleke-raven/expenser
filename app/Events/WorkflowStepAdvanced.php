<?php

namespace App\Events;

use App\Models\ModelHasWorkflow;
use App\Models\WorkflowStepAction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepAdvanced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ModelHasWorkflow $mhw,
        public readonly WorkflowStepAction $action,
    ) {}
}
