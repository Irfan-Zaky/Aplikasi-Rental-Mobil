<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mobils', function (Blueprint $table): void {
            $table->string('status', 20)->default('Tersedia')->change();
            $table->unsignedInteger('harga_sewa')->change();
            $table->softDeletes();
            $table->index('merk');
            $table->index('jenis');
            $table->index('status');
        });

        DB::table('mobils')->where('status', 'Perawatan')->update(['status' => 'Perbaikan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mobils')->where('status', 'Perbaikan')->update(['status' => 'Perawatan']);

        Schema::table('mobils', function (Blueprint $table): void {
            $table->dropIndex(['merk']);
            $table->dropIndex(['jenis']);
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->unsignedBigInteger('harga_sewa')->change();
            $table->enum('status', ['Tersedia', 'Disewa', 'Perawatan'])->default('Tersedia')->change();
        });
    }
};
