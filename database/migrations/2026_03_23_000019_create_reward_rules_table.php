<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reward_type_id');
            $table->foreign('reward_type_id')->references('id')->on('reward_types')->cascadeOnDelete();
            $table->string('dimension');
            $table->string('operator');
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_rules');
    }
};
