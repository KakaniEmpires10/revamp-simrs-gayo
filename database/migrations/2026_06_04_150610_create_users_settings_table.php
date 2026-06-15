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
        if (! Schema::hasTable('users_settings')) {
            Schema::create('users_settings', function (Blueprint $table) {
                $table->charset = 'latin1';
                $table->collation = 'latin1_swedish_ci';

                $table->id();

                $table->string('user_id', 700)
                    ->charset('latin1')
                    ->collation('latin1_swedish_ci');

                $table->string('key', 100)
                    ->charset('latin1')
                    ->collation('latin1_swedish_ci');

                $table->longText('value')->nullable();

                $table->string('type', 30)
                    ->default('string')
                    ->charset('latin1')
                    ->collation('latin1_swedish_ci');

                $table->timestamps();

                $table->unique(['user_id', 'key'], 'users_settings_user_id_key_unique');
                $table->index('key', 'users_settings_key_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users_settings')) {
            Schema::dropIfExists('users_settings');
        }
    }
};
