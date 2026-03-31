<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_recipients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('reward_recipients', function (Blueprint $table) {
            $table->dropColumn(['name', 'email']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
