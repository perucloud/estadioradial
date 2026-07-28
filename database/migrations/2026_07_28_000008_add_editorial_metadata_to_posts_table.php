<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('image_credit')->nullable();
            $table->string('image_license')->nullable();
            $table->unsignedSmallInteger('editorial_priority')->default(50)->index();
            $table->boolean('is_homepage_hidden')->default(false)->index();
            $table->timestamp('pinned_until')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'source_name',
                'source_url',
                'image_credit',
                'image_license',
                'editorial_priority',
                'is_homepage_hidden',
                'pinned_until',
            ]);
        });
    }
};
