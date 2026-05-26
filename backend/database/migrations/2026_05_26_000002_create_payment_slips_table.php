<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_slips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->string('slip_image');
            $table->decimal('amount', 12, 2);
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['payment_method_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_slips');
    }
};
