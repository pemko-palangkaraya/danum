<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nip', 18)->nullable()->unique();
            $table->string('pangkat', 100)->nullable();
            $table->string('golongan', 20)->nullable();
            $table->string('status_pegawai', 50)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_pensiun')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
