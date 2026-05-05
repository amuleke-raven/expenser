<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN; recreate the table with user_id nullable.
        Schema::disableForeignKeyConstraints();

        Schema::create('pending_payments_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payable_id');
            $table->string('payable_type');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payable_id', 'payable_type']);
            $table->index(['status']);
        });

        DB::statement('INSERT INTO pending_payments_new SELECT * FROM pending_payments');

        Schema::drop('pending_payments');
        Schema::rename('pending_payments_new', 'pending_payments');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Remove rows with NULL user_id before restoring the NOT NULL constraint.
        DB::table('pending_payments')->whereNull('user_id')->delete();

        Schema::disableForeignKeyConstraints();

        Schema::create('pending_payments_old', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payable_id');
            $table->string('payable_type');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payable_id', 'payable_type']);
            $table->index(['status']);
        });

        DB::statement('INSERT INTO pending_payments_old SELECT * FROM pending_payments');

        Schema::drop('pending_payments');
        Schema::rename('pending_payments_old', 'pending_payments');

        Schema::enableForeignKeyConstraints();
    }
};
