<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('chatbot_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 40)->unique();
            $table->string('menu_number', 5)->nullable()->index();
            $table->json('synonyms')->nullable();
            $table->string('action', 20)->default('static');
            $table->text('reply_template')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('last_keyword', 40)->nullable();
            $table->dateTime('handover_until')->nullable();
            $table->timestamps();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('direction', 5);
            $table->text('message');
            $table->foreignId('matched_keyword_id')->nullable()->constrained('chatbot_keywords')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_sessions');
        Schema::dropIfExists('chatbot_keywords');
    }
};
