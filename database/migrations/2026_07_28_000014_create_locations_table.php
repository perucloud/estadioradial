<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('type', 20)->index();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('ubigeo', 12)->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(100)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
