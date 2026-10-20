<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->string('check_in_photo')->nullable()->after('check_out');
            $t->string('check_out_photo')->nullable()->after('check_in_photo');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn(['check_in_photo', 'check_out_photo']);
        });
    }
};
