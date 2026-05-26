<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_status', 40)->default('unpaid')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')
            ->whereNotIn('payment_status', ['unpaid', 'paid', 'failed', 'refunded', 'partial_refund'])
            ->update(['payment_status' => 'unpaid']);

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_status', ['unpaid', 'paid', 'failed', 'refunded', 'partial_refund'])
                ->default('unpaid')
                ->change();
        });
    }
};
