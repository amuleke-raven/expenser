<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->boolean('allows_custom_message')->default(false)->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->dropColumn('allows_custom_message');
        });
    }
};
