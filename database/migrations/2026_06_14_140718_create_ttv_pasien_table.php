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
        Schema::create('ttv_pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rawat', 17)->charset('latin1')->collation('latin1_swedish_ci')->unique();
            $table->string('suhu_tubuh', 5)->nullable();
            $table->string('tensi', 8)->nullable();
            $table->unsignedSmallInteger('nadi')->nullable();
            $table->unsignedTinyInteger('respirasi')->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->string('tinggi', 5)->nullable();
            $table->string('berat', 5)->nullable();
            $table->string('gcs', 10)->nullable();
            $table->string('kesadaran', 20)->nullable();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
            $table->timestamps();

            $table->foreign('no_rawat')
                ->references('no_rawat')
                ->on('reg_periksa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ttv_pasien');
    }
};
