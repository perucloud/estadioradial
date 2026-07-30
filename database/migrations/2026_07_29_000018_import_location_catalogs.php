<?php

use App\Support\LocationCatalogImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.migration_schema_only')) {
            return;
        }

        app(LocationCatalogImporter::class)->import();
    }

    public function down(): void
    {
        // No se eliminan ubicaciones para proteger noticias y datos manuales.
    }
};
