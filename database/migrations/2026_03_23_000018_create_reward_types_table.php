<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_client_based')->default(false);
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->unsignedBigInteger('workflow_id')->nullable();
            $table->foreign('workflow_id')->references('id')->on('workflows')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_types');
    }
};
