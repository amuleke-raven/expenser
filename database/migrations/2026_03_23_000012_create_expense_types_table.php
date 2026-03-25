<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_group_id');
            $table->foreign('expense_group_id')->references('id')->on('expense_groups');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->unsignedBigInteger('workflow_id')->nullable();
            $table->foreign('workflow_id')->references('id')->on('workflows')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};
