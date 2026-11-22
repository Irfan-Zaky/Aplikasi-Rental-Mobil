<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('users', function (Blueprint $table) {
            $table->string('ktp_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('verification_status')->default('belum');
            $table->string('rejection_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ktp_path', 'selfie_path', 'verification_status', 'rejection_note']);
        });
    }
};
