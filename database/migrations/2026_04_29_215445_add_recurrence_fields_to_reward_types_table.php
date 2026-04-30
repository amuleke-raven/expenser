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
            $table->boolean('is_recurrent')->default(false)->after('requires_attachments');
            $table->string('recurrence_frequency')->nullable()->after('is_recurrent');
            $table->date('recurrence_start_date')->nullable()->after('recurrence_frequency');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurrent',
                'recurrence_frequency',
                'recurrence_start_date',
                'recurrence_end_date',
            ]);
        });
    }
};
