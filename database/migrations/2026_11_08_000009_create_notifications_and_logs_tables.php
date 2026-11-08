<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50);
            $table->string('name', 100);
            $table->string('channel', 15);
            $table->string('subject', 150)->nullable();
            $table->text('body');
            $table->json('available_placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_role', 15);
            $table->string('recipient_name', 120)->nullable();
            $table->string('recipient_address', 150)->index();
            $table->string('channel', 15)->index();
            $table->string('template_key', 50)->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('message');
            $table->string('attachment_path', 255)->nullable();
            $table->nullableMorphs('notifiable_context', 'idx_notif_logs_context');
            $table->string('status', 15)->default('queued')->index();
            $table->json('provider_response')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
