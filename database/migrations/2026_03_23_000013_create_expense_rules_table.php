<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ruleable_id');
            $table->string('ruleable_type');
            $table->string('dimension');
            $table->string('operator');
            $table->json('value');
            $table->timestamps();

            $table->index(['ruleable_id', 'ruleable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_rules');
    }
};
