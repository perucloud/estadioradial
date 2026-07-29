<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('image')->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('display_order')->default(100)->after('is_active')->index();
            $table->softDeletes();
        });

        Schema::create('program_user', function (Blueprint $table) {
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['program_id', 'user_id']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('ends_at')->index();
        });

        Schema::table('streams', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->after('cover')->constrained('media')->nullOnDelete();
            $table->boolean('is_primary')->default(false)->after('is_active')->index();
            $table->string('fallback_message', 255)->nullable()->after('is_primary');
            $table->softDeletes();
        });

        foreach (['audio', 'video'] as $type) {
            $firstId = DB::table('streams')->where('type', $type)->orderBy('sort_order')->value('id');
            if ($firstId) {
                DB::table('streams')->where('id', $firstId)->update(['is_primary' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
            $table->dropColumn(['is_primary', 'fallback_message', 'deleted_at']);
        });
        Schema::table('schedules', fn (Blueprint $table) => $table->dropColumn('is_active'));
        Schema::dropIfExists('program_user');
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
            $table->dropColumn(['display_order', 'deleted_at']);
        });
    }
};
