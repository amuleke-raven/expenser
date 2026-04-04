<?php

use App\Models\Expense;
use App\Models\RewardRecipient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->string('payment_source')->nullable()->after('payable_type');
            $table->unsignedBigInteger('recipient_id')->nullable()->after('payment_source');
        });

        DB::table('pending_payments')
            ->where('payable_type', Expense::class)
            ->update([
                'payment_source' => 'expense',
                'recipient_id' => DB::raw('user_id'),
            ]);

        DB::table('pending_payments')
            ->where('payable_type', RewardRecipient::class)
            ->update([
                'payment_source' => 'reward',
                'recipient_id' => DB::raw('payable_id'),
            ]);

        Schema::table('pending_payments', function (Blueprint $table) {
            $table->string('payment_source')->nullable(false)->change();
            $table->unsignedBigInteger('recipient_id')->nullable(false)->change();
            $table->index(['recipient_id', 'payment_source']);
        });

        Schema::table('pending_payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('payable_type');
        });

        DB::table('pending_payments')
            ->where('payment_source', 'expense')
            ->update(['user_id' => DB::raw('recipient_id')]);

        Schema::table('pending_payments', function (Blueprint $table) {
            $table->dropIndex(['recipient_id', 'payment_source']);
            $table->dropColumn(['recipient_id', 'payment_source']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
