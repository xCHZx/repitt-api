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
        Schema::table('user_stamp_cards', function (Blueprint $table) {
            $table->string('userstampcard_repitt_code')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_stamp_cards', function (Blueprint $table) {
            $table->dropColumn('userstampcard_repitt_code');
        });
    }
};
