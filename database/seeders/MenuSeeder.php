<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Starting navbar and footer links. Seeded only while there are none, so menus the admin has
 * edited are never overwritten.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuItem::query()->exists()) {
            return;
        }

        // location, type, target, [label en, label id] (null label = the CTA label from Pengaturan Situs), is_button
        $items = [
            [MenuItem::HEADER, 'section', 'business', ['Business', 'Bisnis'], false],
            [MenuItem::HEADER, 'section', 'products', ['Products', 'Produk'], false],
            [MenuItem::HEADER, 'section', 'quality', ['Quality', 'Mutu'], false],
            [MenuItem::HEADER, 'section', 'sustainability', ['Sustainability', 'Keberlanjutan'], false],
            [MenuItem::HEADER, 'page', 'company', ['Company', 'Perusahaan'], false],
            [MenuItem::HEADER, 'page', 'news', ['Blog', 'Blog'], false],
            [MenuItem::HEADER, 'section', 'contact', null, true],
            [MenuItem::FOOTER_EXPLORE, 'section', 'business', ['Business', 'Bisnis'], false],
            [MenuItem::FOOTER_EXPLORE, 'section', 'products', ['Products', 'Produk'], false],
            [MenuItem::FOOTER_EXPLORE, 'section', 'quality', ['Quality', 'Mutu'], false],
            [MenuItem::FOOTER_EXPLORE, 'section', 'sustainability', ['Sustainability', 'Keberlanjutan'], false],
            [MenuItem::FOOTER_EXPLORE, 'page', 'news', ['Blog', 'Blog'], false],
            [MenuItem::FOOTER_COMPANY, 'page', 'company', ['Company Profile', 'Profil Perusahaan'], false],
            [MenuItem::FOOTER_COMPANY, 'page', 'company#leadership', ['Leadership', 'Kepemimpinan'], false],
            [MenuItem::FOOTER_COMPANY, 'page', 'company#certifications', ['Certifications', 'Sertifikasi'], false],
        ];

        foreach ($items as $i => [$location, $type, $target, $label, $button]) {
            $item = MenuItem::query()->create([
                'location' => $location,
                'type' => $type,
                'target' => $target,
                'label' => $label ? ['en' => $label[0], 'id' => $label[1]] : null,
                'is_button' => $button,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
            $item->markTranslationsReviewed('id')->save();
        }
    }
}
