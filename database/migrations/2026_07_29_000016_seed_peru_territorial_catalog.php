<?php

use Database\Seeders\LocationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LocationSeeder::class)->run();
    }

    public function down(): void
    {
        // El catálogo no se elimina para proteger ubicaciones creadas o usadas
        // por publicaciones después de aplicar esta migración.
    }
};
