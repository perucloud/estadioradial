<?php

use App\Support\LocationCatalogImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LocationCatalogImporter::class)->import();
    }

    public function down(): void
    {
        // No se eliminan ubicaciones para proteger noticias y datos manuales.
    }
};
