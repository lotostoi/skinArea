<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('status', 16)->default('posted')->after('type');
            $table->string('balance_type', 16)->default('main')->after('status');
            $table->timestamp('posted_at')->nullable()->after('metadata');
            $table->timestamp('reversed_at')->nullable()->after('posted_at');
            $table->unsignedBigInteger('reverses_transaction_id')->nullable()->after('reversed_at');
            $table->string('idempotency_key', 64)->nullable()->after('reverses_transaction_id');

            $table->foreign('reverses_transaction_id')
                ->references('id')->on('transactions')
                ->nullOnDelete();

            $table->unique('idempotency_key');
            $table->index(['user_id', 'balance_type', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['reverses_transaction_id']);
        });

        DB::table('transactions')
            ->whereNull('posted_at')
            ->update([
                'status' => 'posted',
                'balance_type' => 'main',
                'posted_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['reverses_transaction_id']);
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['user_id', 'balance_type', 'status', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['reverses_transaction_id']);
            $table->dropColumn([
                'status',
                'balance_type',
                'posted_at',
                'reversed_at',
                'reverses_transaction_id',
                'idempotency_key',
            ]);
        });
    }
};
