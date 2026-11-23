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
        Schema::create('booking_history', function (Blueprint $table): void {
            $table->bigIncrements('history_id');
            $table->foreignId('rental_id')->constrained('rental', 'rental_id')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->string('status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('account', 'account_id')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['rental_id', 'sequence_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_history');
    }
};
