<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tenant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('nik')->nullable();
            $table->char('nik_hash', 64)->nullable()->index();
            $table->string('ktp_photo_path', 255)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('origin_address')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('institution_name', 150)->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_phone', 20)->nullable()->index();
            $table->string('guardian_email', 150)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->text('emergency_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_profiles');
    }
};
