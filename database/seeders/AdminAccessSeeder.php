<?php

namespace Database\Seeders;

use App\Support\AdminAccess;
use Illuminate\Database\Seeder;

class AdminAccessSeeder extends Seeder
{
    public function run(): void
    {
        AdminAccess::sync();
    }
}
