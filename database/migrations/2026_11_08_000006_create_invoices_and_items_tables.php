<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->restrictOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained('leases')->restrictOnDelete();
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            $table->string('type', 20)->index();
            $table->unsignedInteger('cycle_index')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('issue_date');
            $table->date('due_date')->index();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('penalty_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('minimum_pay_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status', 15)->default('unpaid')->index();
            $table->dateTime('paid_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pdf_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lease_id', 'type', 'period_start'], 'idx_inv_lease_period');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('type', 15)->index();
            $table->string('description', 200);
            $table->unsignedSmallInteger('qty')->default(1);
            $table->bigInteger('unit_amount');
            $table->bigInteger('amount');
            $table->boolean('is_waived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
