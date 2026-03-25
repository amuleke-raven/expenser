<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_step_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_has_workflow_id');
            $table->foreign('model_has_workflow_id')->references('id')->on('model_has_workflows')->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_step_id');
            $table->foreign('workflow_step_id')->references('id')->on('workflow_steps');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_actions');
    }
};
