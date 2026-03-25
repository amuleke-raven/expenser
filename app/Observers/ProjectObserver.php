<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectObserver
{
    public function updating(Project $project): void
    {
        if ($project->isDirty('is_active') && ! $project->is_active && $project->getOriginal('is_active')) {
            DB::transaction(function () use ($project) {
                $defaultProject = Project::default()->firstOrFail();

                $affectedUsers = User::where('default_project_id', $project->id)
                    ->whereDoesntHave('projects', fn ($q) => $q->where('project_id', '!=', $project->id)
                        ->where('is_active', true))
                    ->get();

                $affectedUsers->each(fn ($u) => $u->update(['default_project_id' => $defaultProject->id]));
            });
        }
    }
}
