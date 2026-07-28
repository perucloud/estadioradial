<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_menu')->default(true)->index();
            $table->boolean('show_on_home')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(100)->index();
            $table->unsignedSmallInteger('relevance_weight')->default(50)->index();
            $table->unsignedTinyInteger('homepage_limit')->default(4);
            $table->string('homepage_layout', 30)->default('standard');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'show_in_menu',
                'show_on_home',
                'display_order',
                'relevance_weight',
                'homepage_limit',
                'homepage_layout',
            ]);
        });
    }
};
