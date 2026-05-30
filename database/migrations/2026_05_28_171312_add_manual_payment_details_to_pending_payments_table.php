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
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->string('manual_payment_details')->nullable()->after('payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->dropColumn('manual_payment_details');
        });
    }
};
