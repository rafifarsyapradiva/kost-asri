<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('penalty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index()->constrained('invoices')->restrictOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained('leases')->restrictOnDelete();
            $table->unsignedInteger('cycle_index')->nullable();
            $table->string('action', 15)->index();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('reason', 255)->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('guardian_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 15);
            $table->string('period', 10);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['doc_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('penalty_logs');
    }
};
