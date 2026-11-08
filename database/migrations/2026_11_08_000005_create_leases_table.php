<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained('reservations')->restrictOnDelete();
            $table->foreignId('user_id')->index()->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->index()->constrained('rooms')->restrictOnDelete();
            $table->string('rent_type', 15)->index();
            $table->string('prorata_option', 15)->nullable();
            $table->unsignedBigInteger('daily_rate');
            $table->unsignedBigInteger('weekly_rate');
            $table->unsignedBigInteger('monthly_rate');
            $table->date('start_date');
            $table->date('billed_until')->index();
            $table->date('paid_until')->index();
            $table->unsignedInteger('cycle_count')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->date('planned_checkout_date')->nullable()->index();
            $table->string('status', 15)->default('active')->index();
            $table->dateTime('checked_in_at');
            $table->dateTime('checked_out_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('checkout_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
