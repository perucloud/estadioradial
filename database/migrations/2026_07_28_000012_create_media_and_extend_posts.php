<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->json('variants')->nullable();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('alt_text');
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->string('license')->nullable();
            $table->string('checksum', 64)->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('image')
                ->constrained('media')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->after('author')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('updated_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable()->after('published_at')->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->softDeletes();
        });

        Schema::create('media_post', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['media_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_post');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'scheduled_for',
                'seo_title',
                'seo_description',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('media');
    }
};
