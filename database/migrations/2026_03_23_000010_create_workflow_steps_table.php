<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->foreign('workflow_id')->references('id')->on('workflows')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order');
            $table->string('action_type');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
