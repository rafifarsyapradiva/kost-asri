<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index()->constrained('invoices')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('method', 15)->index();
            $table->string('order_id', 60)->nullable()->unique();
            $table->string('snap_token', 100)->nullable();
            $table->dateTime('snap_expires_at')->nullable();
            $table->string('payment_channel', 40)->nullable();
            $table->string('midtrans_transaction_id', 80)->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('status', 15)->default('pending')->index();
            $table->dateTime('paid_at')->nullable()->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('raw_notification')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('payment_id')->unique()->constrained('payments')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->dateTime('issued_at');
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
    }
};
