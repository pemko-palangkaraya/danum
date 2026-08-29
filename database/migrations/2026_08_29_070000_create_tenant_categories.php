<?php

declare(strict_types=1);

use IlluminateDatabaseMigrationsMigration;
use IlluminateDatabaseSchemaBlueprint;
use IlluminateSupportFacadesDB;
use IlluminateSupportFacadesSchema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $categories = [
            ['sekretariat-daerah', 'Sekretariat Daerah'],
            ['sekretariat-dprd', 'Sekretariat DPRD'],
            ['inspektorat', 'Inspektorat'],
            ['dinas', 'Dinas'],
            ['badan', 'Badan'],
            ['satuan-polisi-pamong-praja', 'Satuan Polisi Pamong Praja'],
            ['kecamatan', 'Kecamatan'],
            ['kelurahan', 'Kelurahan'],
            ['uptd', 'UPTD'],
            ['unit-pelaksana-teknis', 'Unit Pelaksana Teknis'],
            ['rumah-sakit-daerah', 'Rumah Sakit Daerah'],
            ['puskesmas', 'Puskesmas'],
            ['blud', 'Badan Layanan Umum Daerah (BLUD)'],
            ['lainnya', 'Lainnya'],
        ];

        $now = now();
        foreach ($categories as $index => [$code, $name]) {
            DB::table('tenant_categories')->insert([
                'code' => $code,
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['tenant_category_id']);
            $table->dropColumn('tenant_category_id');
        });

        Schema::dropIfExists('tenant_categories');
    }
};
