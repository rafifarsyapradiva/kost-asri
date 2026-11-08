<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->index();
            $table->string('event_key', 80)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
