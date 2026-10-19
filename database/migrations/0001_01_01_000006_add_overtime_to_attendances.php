<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->unsignedInteger('overtime_minutes')->default(0)->after('late_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn('overtime_minutes');
        });
    }
};
