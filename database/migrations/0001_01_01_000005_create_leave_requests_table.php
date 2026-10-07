<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // izin | sakit | cuti
            $t->date('start_date');
            $t->date('end_date');
            $t->text('reason');
            $t->string('attachment')->nullable();
            $t->string('status')->default('pending'); // pending | disetujui | ditolak
            $t->text('admin_note')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
