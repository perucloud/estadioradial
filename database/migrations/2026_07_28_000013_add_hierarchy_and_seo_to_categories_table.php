<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('icon', 100)->nullable()->after('color');
            $table->string('seo_title', 70)->nullable()->after('homepage_layout');
            $table->string('seo_description', 170)->nullable()->after('seo_title');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['icon', 'seo_title', 'seo_description', 'deleted_at']);
        });
    }
};
