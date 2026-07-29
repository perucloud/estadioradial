<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('source', 100)->nullable()->after('ubigeo')->index();
            $table->string('source_key', 100)->nullable()->after('source');
            $table->unique(['source', 'source_key'], 'locations_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique('locations_source_key_unique');
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'source_key']);
        });
    }
};
