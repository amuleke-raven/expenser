<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->unsignedBigInteger('fixed_currency_id')->nullable()->after('fixed_amount');
            $table->foreign('fixed_currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->dropForeign(['fixed_currency_id']);
            $table->dropColumn('fixed_currency_id');
        });
    }
};
