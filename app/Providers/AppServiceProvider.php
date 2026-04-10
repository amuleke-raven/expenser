<?php

namespace App\Providers;

use App\Events\ExpenseApproved;
use App\Events\ExpenseSubmitted;
use App\Events\RewardApproved;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowRejected;
use App\Listeners\HandleWorkflowCompleted;
use App\Listeners\HandleWorkflowRejected;
use App\Listeners\NotifyAccountingOnExpenseApproval;
use App\Listeners\NotifyRecipientsOnRewardApproval;
use App\Listeners\TriggerExpenseWorkflow;
use App\Models\Expense;
use App\Models\ExpenseLineItem;
use App\Models\Project;
use App\Models\Ticket;
use App\Observers\ExpenseLineItemObserver;
use App\Observers\ExpenseObserver;
use App\Observers\ProjectObserver;
use App\Observers\TicketObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Expense::observe(ExpenseObserver::class);
        ExpenseLineItem::observe(ExpenseLineItemObserver::class);
        Project::observe(ProjectObserver::class);
        Ticket::observe(TicketObserver::class);

        Model::preventLazyLoading(app()->isLocal());

        Event::listen(ExpenseSubmitted::class, TriggerExpenseWorkflow::class);
        Event::listen(ExpenseApproved::class, NotifyAccountingOnExpenseApproval::class);
        Event::listen(RewardApproved::class, NotifyRecipientsOnRewardApproval::class);
        Event::listen(WorkflowCompleted::class, HandleWorkflowCompleted::class);
        Event::listen(WorkflowRejected::class, HandleWorkflowRejected::class);
    }
}
