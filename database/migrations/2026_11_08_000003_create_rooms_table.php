<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->unsignedTinyInteger('floor')->index();
            $table->unsignedTinyInteger('grid_row')->default(1);
            $table->unsignedTinyInteger('grid_col')->default(1);
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete();
            $table->string('status', 20)->default('available')->index();
            $table->string('maintenance_note', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
