<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->boolean('requires_attachments')->default(false)->after('allows_custom_message');
        });
    }

    public function down(): void
    {
        Schema::table('reward_types', function (Blueprint $table) {
            $table->dropColumn('requires_attachments');
        });
    }
};
