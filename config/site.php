<?php

return [

    'image_conversions' => env('IMAGE_CONVERSIONS', 'auto'),

    'favicon_ico_path' => env('FAVICON_ICO_PATH'),

    'locales' => [
        'en' => ['prefix' => '', 'og' => 'en_US', 'native' => 'English', 'segments' => ['company' => 'company', 'news' => 'news', 'products' => 'products', 'contact' => 'contact']],
        'id' => ['prefix' => 'id', 'og' => 'id_ID', 'native' => 'Bahasa Indonesia', 'segments' => ['company' => 'perusahaan', 'news' => 'berita', 'products' => 'produk', 'contact' => 'kontak']],
    ],

    'locale_switch_order' => ['id', 'en'],

    'cache_key' => 'jbm.frontend.v2',

    'whatsapp_number' => env('WHATSAPP_NUMBER'),

    /*
     * Retention of the contact form leads (personal data); see App\Console\Commands\PruneLeads.
     * 0 switches a step off.
     */
    'leads' => [
        'anonymize_after_days' => (int) env('LEADS_ANONYMIZE_AFTER_DAYS', 60),
        'delete_after_months' => (int) env('LEADS_DELETE_AFTER_MONTHS', 12),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
