<?php

namespace Database\Seeders;

use App\Support\FrontendData;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Safe to run again: existing content is not overwritten.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            MenuSeeder::class,
            PageSectionSeeder::class,
            CatalogSeeder::class,
            CompanySeeder::class,
        ]);

        FrontendData::flush();
    }
}
