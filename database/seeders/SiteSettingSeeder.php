<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * Starting values for Pengaturan Situs. Contact details are DUMMY placeholders
 * (replace them in the admin before launch). Never overwrites a settings row that
 * already has a company description.
 */
class SiteSettingSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        $settings = SiteSetting::current();

        if ($settings->translation('company_description', 'en') === null) {
            $settings->update([
                'company_name' => 'PT Jaya Berkat Melimpah',
                'legal_name' => 'PT Jaya Berkat Melimpah',
                'company_description' => [
                    'en' => 'PT Jaya Berkat Melimpah is an Indonesian fisheries and seafood company connecting the archipelago\'s waters to global markets through sourcing, processing, and distribution.',
                    'id' => 'PT Jaya Berkat Melimpah adalah perusahaan perikanan dan seafood Indonesia yang menghubungkan perairan nusantara dengan pasar global lewat pengadaan, pengolahan, dan distribusi.',
                ],
                'footer_tagline' => [
                    'en' => 'From the largest archipelago to the global market.',
                    'id' => 'Dari kepulauan terbesar ke pasar global.',
                ],
                'cta_label' => ['en' => 'Request a Quote', 'id' => 'Minta Penawaran'],
                // DUMMY contact details: replace before the site goes live.
                'whatsapp_number' => config('site.whatsapp_number') ?: '+62 21 5551 2345',
                'email' => 'info@jbmelimpah.com',
                'phone' => '+62 21 5551 2345',
                'address' => "Jl. Perikanan Raya No. 10\nJakarta Utara 14440, Indonesia",
                'seo_title' => [
                    'en' => 'PT Jaya Berkat Melimpah | Indonesian Fisheries & Seafood',
                    'id' => 'PT Jaya Berkat Melimpah | Perikanan & Seafood Indonesia',
                ],
                'seo_description' => [
                    'en' => 'PT Jaya Berkat Melimpah is an Indonesian fisheries and seafood company: quality seafood sourced from the archipelago, processed with care and delivered to buyers worldwide.',
                    'id' => 'PT Jaya Berkat Melimpah adalah perusahaan perikanan dan seafood Indonesia: seafood berkualitas dari nusantara, diolah dengan cermat dan dikirim ke pembeli di seluruh dunia.',
                ],
                'translation_glossary' => [
                    ['en' => 'Seafood', 'id' => null],
                    ['en' => 'Cold chain', 'id' => 'rantai dingin'],
                    ['en' => 'Request a Quote', 'id' => 'Minta Penawaran'],
                    ['en' => 'Yellowfin Tuna', 'id' => 'Tuna Sirip Kuning'],
                    ['en' => 'Grouper', 'id' => 'Kerapu'],
                    ['en' => 'Red Snapper', 'id' => 'Kakap Merah'],
                ],
            ]);
            $settings->markTranslationsReviewed('id')->save();
        }

        $this->attachSeedImage($settings, 'logo', 'logo', 512, 512);
        $this->attachSeedImage($settings, 'favicon', 'favicon', 512, 512);
        $this->attachSeedImage($settings, 'og_image', 'og-image', 1200, 630);
    }
}
