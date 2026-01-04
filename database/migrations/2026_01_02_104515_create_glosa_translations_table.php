<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Locales Table
        Schema::create('glosa_locales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'en', 'fr_CA'
            $table->string('name')->nullable(); // e.g. 'English'
            $table->boolean('is_default')->default(false); // e.g. true for 'en'
            $table->timestamps();
        });

        // 2. Keys Table
        Schema::create('glosa_keys', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('messages'); // e.g. 'auth', 'messages'
            $table->string('key'); // e.g. 'welcome_message'
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        // 3. Values Table
        Schema::create('glosa_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained('glosa_keys')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained('glosa_locales')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['key_id', 'locale_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glosa_values');
        Schema::dropIfExists('glosa_keys');
        Schema::dropIfExists('glosa_locales');
    }
};
