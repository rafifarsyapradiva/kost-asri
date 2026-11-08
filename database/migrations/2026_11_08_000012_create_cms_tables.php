<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('icon', 50);
            $table->string('description', 255)->nullable();
            $table->string('category', 20)->default('general');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('category', 20);
            $table->string('image_path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('youtube_url', 255);
            $table->string('youtube_id', 20);
            $table->string('thumbnail_url', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('reservation_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('step_number');
            $table->string('title', 100);
            $table->text('description');
            $table->string('icon', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('avatar_path', 255)->nullable();
            $table->string('origin_info', 100);
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('content');
            $table->string('moderation_status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('nearby_places', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('category', 30);
            $table->string('distance_text', 30);
            $table->unsignedTinyInteger('travel_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('group', 30);
            $table->json('value');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('nearby_places');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('reservation_steps');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('facilities');
    }
};
