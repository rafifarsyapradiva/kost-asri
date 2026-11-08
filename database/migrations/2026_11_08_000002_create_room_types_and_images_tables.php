<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 90)->unique();
            $table->string('room_class', 20)->unique();
            $table->unsignedBigInteger('daily_rate');
            $table->unsignedBigInteger('weekly_rate');
            $table->unsignedBigInteger('monthly_rate');
            $table->decimal('size_m2', 5, 2)->nullable();
            $table->json('facilities_json')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('room_type_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('image_path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('caption', 150)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_images');
        Schema::dropIfExists('room_types');
    }
};
