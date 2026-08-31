<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('citizen_id');
            $table->text('alamat')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('jenis_alamat', 30)->default('domisili');
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->timestamps();

            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
            $table->index(['citizen_id', 'jenis_alamat']);
            $table->index(['rt', 'rw']);
            $table->index(['kelurahan', 'kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_addresses');
    }
};
