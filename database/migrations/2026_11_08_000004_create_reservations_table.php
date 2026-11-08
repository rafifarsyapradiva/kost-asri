<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 25)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->string('rent_type', 15)->index();
            $table->string('prorata_option', 15)->nullable();
            $table->string('payment_scheme', 10);
            $table->string('preferred_method', 15);
            $table->string('source', 10)->default('online');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('check_in_date')->index();
            $table->date('check_out_date')->nullable()->index();
            $table->unsignedSmallInteger('duration_qty')->default(1);
            $table->unsignedBigInteger('rate_snapshot');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('dp_amount')->default(0);
            $table->date('initial_billed_until');
            $table->string('status', 20)->default('pending_payment')->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status', 'check_in_date', 'check_out_date'], 'idx_rsv_room_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
