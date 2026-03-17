<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_tag', function (Blueprint $table) {
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['expense_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_tag');
    }
};
