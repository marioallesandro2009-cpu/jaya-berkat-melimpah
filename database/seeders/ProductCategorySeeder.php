<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Starting categories (marine, brackish and freshwater). Seeded only while there are none, so
 * categories the admin has edited or removed are never brought back. The sample products are
 * marine species, so they are filed under "Marine".
 */
class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (ProductCategory::query()->exists()) {
            return;
        }

        $rows = [
            ['marine', ['Marine', 'Air laut'], ['Wild-caught species from open sea and reef waters.', 'Spesies tangkapan liar dari laut lepas dan perairan karang.'], '#7CC4E4'],
            ['brackish-water', ['Brackish water', 'Air payau'], ['Species from estuaries, mangroves and coastal ponds.', 'Spesies dari muara, mangrove, dan tambak pesisir.'], '#2F9E8F'],
            ['freshwater', ['Freshwater', 'Air tawar'], ['Species from rivers, lakes and freshwater farms.', 'Spesies dari sungai, danau, dan budidaya air tawar.'], '#D9B26F'],
        ];

        foreach ($rows as $i => [$slug, $name, $description, $color]) {
            $category = ProductCategory::query()->create([
                'slug' => $slug,
                'name' => ['en' => $name[0], 'id' => $name[1]],
                'description' => ['en' => $description[0], 'id' => $description[1]],
                'color' => $color,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
            $category->markTranslationsReviewed('id')->save();
        }

        $marine = ProductCategory::query()->where('slug', 'marine')->value('id');
        Product::query()->whereNull('category_id')->update(['category_id' => $marine]);
    }
}
