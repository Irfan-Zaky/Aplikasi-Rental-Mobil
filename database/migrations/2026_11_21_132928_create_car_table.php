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
        Schema::create('car', function (Blueprint $table) {
            $table->bigIncrements('car_id');
            $table->string('kode_mobil')->unique();
            $table->string('merk');
            $table->string('nama_mobil');
            $table->string('jenis');
            $table->enum('status', ['Tersedia', 'Disewa', 'Perawatan'])->default('Tersedia');
            $table->unsignedBigInteger('harga_sewa');
            $table->string('nomor_polisi')->unique();
            $table->text('spesifikasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car');
    }
};
