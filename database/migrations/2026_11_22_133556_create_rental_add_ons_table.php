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
        Schema::create('rental_add_ons', function (Blueprint $table): void {
            $table->foreignId('rental_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('handover_method', 20)->nullable();
            $table->string('pickup_point')->nullable();
            $table->unsignedInteger('driver_fee')->default(0);
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_add_ons');
    }
};
