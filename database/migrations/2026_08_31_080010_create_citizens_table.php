<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('nik', 16);
            $table->string('nama_lengkap');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->string('agama', 40)->nullable();
            $table->string('status_perkawinan', 30)->nullable();
            $table->string('pendidikan', 100)->nullable();
            $table->string('pekerjaan', 150)->nullable();
            $table->string('kewarganegaraan', 50)->default('WNI');
            $table->string('no_passport', 50)->nullable();
            $table->string('no_kitap', 50)->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('nik_ayah', 16)->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('nik_ibu', 16)->nullable();
            $table->string('status_kependudukan', 30)->default('active');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'nik']);
            $table->index(['tenant_id', 'nama_lengkap']);
            $table->index(['tenant_id', 'tanggal_lahir']);
            $table->index(['tenant_id', 'jenis_kelamin']);
            $table->index(['tenant_id', 'status_kependudukan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
