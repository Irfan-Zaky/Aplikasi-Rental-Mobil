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
        Schema::create('rentals', function (Blueprint $table): void {
            $table->id();
            $table->string('trx_code', 20)->unique();
            $table->string('booking_code', 20)->nullable()->unique();
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->timestamp('invoice_issued_at')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('mobil_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedSmallInteger('duration');
            $table->unsignedInteger('price_per_day');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('total_rental');
            $table->unsignedInteger('late_fee')->default(0);
            $table->boolean('self_driver');
            $table->string('customer_name', 100);
            $table->text('customer_nik');
            $table->string('customer_phone', 15);
            $table->string('customer_ktp_photo');
            $table->string('customer_selfie_photo');
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('status', 30)->default('Menunggu Pembayaran');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['mobil_id', 'start_date', 'end_date']);
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
