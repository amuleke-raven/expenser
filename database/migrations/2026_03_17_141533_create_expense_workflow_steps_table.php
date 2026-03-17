<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actioned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->unsignedInteger('step_order');
            $table->unique(['expense_id', 'workflow_step_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_workflow_steps');
    }
};
