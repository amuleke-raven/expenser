<?php

namespace App\Events;

use App\Models\Reward;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RewardInitiated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Reward $reward) {}
}
