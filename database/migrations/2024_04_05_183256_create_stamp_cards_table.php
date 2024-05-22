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
        Schema::create('stamp_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->integer('required_stamps');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('stamp_icon_path')->nullable();
            $table->string('primary_color')->nullable();
            $table->unsignedBigInteger('business_id');
            $table->string('reward');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_active')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_cards');
    }
};
