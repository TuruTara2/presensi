<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kunci perangkat: satu akun karyawan terikat ke satu perangkat (browser)
        Schema::table('users', function (Blueprint $t) {
            $t->string('device_hash', 64)->nullable()->after('is_active');
            $t->string('device_name')->nullable()->after('device_hash');
            $t->timestamp('device_bound_at')->nullable()->after('device_name');
        });

        // Permintaan login dari perangkat baru (menunggu persetujuan admin)
        Schema::create('device_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('device_hash', 64);
            $t->string('user_agent')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('status')->default('pending'); // pending | disetujui | ditolak
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamps();
        });

        // Daftar IP / range jaringan kantor yang boleh menampilkan QR
        Schema::create('office_networks', function (Blueprint $t) {
            $t->id();
            $t->string('label');
            $t->string('cidr', 64); // contoh: 192.168.1.0/24 atau 103.12.34.56
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_networks');
        Schema::dropIfExists('device_requests');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['device_hash', 'device_name', 'device_bound_at']);
        });
    }
};
