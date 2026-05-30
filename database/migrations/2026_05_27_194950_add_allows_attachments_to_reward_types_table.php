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
            $table->boolean('allows_attachments')->default(false)->after('allows_custom_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->dropColumn('allows_attachments');
        });
    }
};
