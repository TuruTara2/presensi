<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->boolean('is_manual')->default(false)->after('overtime_minutes'); // diinput/diedit admin
            $t->unsignedBigInteger('manual_by')->nullable()->after('is_manual');   // admin yang menginput
            $t->string('note', 300)->nullable()->after('manual_by');               // keterangan
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn(['is_manual', 'manual_by', 'note']);
        });
    }
};
