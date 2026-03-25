<?php

namespace App\Events;

use App\Models\ModelHasWorkflow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ModelHasWorkflow $mhw) {}
}
