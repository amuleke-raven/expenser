<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_expense_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('expense_group_id');
            $table->foreign('expense_group_id')->references('id')->on('expense_groups')->cascadeOnDelete();

            $table->primary(['role_id', 'expense_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_expense_groups');
    }
};
