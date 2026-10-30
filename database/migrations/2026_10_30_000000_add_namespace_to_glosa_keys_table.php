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
        Schema::table('glosa_keys', function (Blueprint $table) {
            // '*' is Laravel's global namespace; others match loadTranslationsFrom() hints, e.g. 'client'.
            $table->string('namespace')->default('*')->after('id');
        });

        Schema::table('glosa_keys', function (Blueprint $table) {
            $table->dropUnique(['group', 'key']);
            $table->unique(['namespace', 'group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glosa_keys', function (Blueprint $table) {
            $table->dropUnique(['namespace', 'group', 'key']);
            $table->unique(['group', 'key']);
        });

        Schema::table('glosa_keys', function (Blueprint $table) {
            $table->dropColumn('namespace');
        });
    }
};
