<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_has_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->foreign('workflow_id')->references('id')->on('workflows');
            $table->unsignedBigInteger('workflowable_id');
            $table->string('workflowable_type');
            $table->unsignedInteger('current_step')->default(1);
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflowable_id', 'workflowable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_workflows');
    }
};
