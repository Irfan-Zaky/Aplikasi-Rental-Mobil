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
        Schema::create('qris', function (Blueprint $table): void {
            $table->foreignId('payment_id')->primary()->constrained('payment', 'payment_id')->cascadeOnDelete();
            $table->string('qr_ref', 100)->unique();
            $table->text('qr_code');
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qris');
    }
};
