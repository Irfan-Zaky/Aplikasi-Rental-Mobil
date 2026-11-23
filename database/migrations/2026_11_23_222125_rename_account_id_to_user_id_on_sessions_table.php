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
        Schema::table('sessions', function (Blueprint $table): void {
            $table->renameColumn('account_id', 'user_id');
            $table->renameIndex('sessions_account_id_index', 'sessions_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table): void {
            $table->renameIndex('sessions_user_id_index', 'sessions_account_id_index');
            $table->renameColumn('user_id', 'account_id');
        });
    }
};
