<?php

/**
 * Indonesian version of the demo seafood catalogue (see seafood_catalog.php). Written by hand, not by a
 * machine translation service. Fish names, cut names that are industry terms (loin, saku, akami, fillet...)
 * and product names stay as they are used in the trade; the sentences around them are Indonesian.
 * The seeder fills a language only where it is still empty, so texts edited in the admin are never overwritten.
 */

return [
    // slug => [name, description]
    'categories' => [
        'tuna' => ['Tuna', 'Tuna madidihang (yellowfin), sirip biru (bluefin), dan mata besar (bigeye) dalam potongan utuh, loin, saku, steak, dan potongan sashimi premium.'],
        'salmon' => ['Salmon', 'Salmon Atlantik dalam bentuk utuh, fillet, saku, belly, dan steak untuk sashimi, sushi, dan foodservice.'],
        'mackerel' => ['Makerel', 'Makerel Spanyol (tenggiri) dalam bentuk fillet dan saku untuk dipanggang, di-searing, dan sashimi.'],
        'snapper' => ['Kakap', 'Kakap merah dalam bentuk utuh dan fillet.'],
        'yellowtail' => ['Yellowtail', 'Hamachi (yellowtail) dalam bentuk loin, saku, dan fillet untuk sashimi dan sushi.'],
        'sea-bream' => ['Sea Bream', 'Tai (red sea bream) dalam bentuk utuh, fillet, dan saku.'],
        'other-sashimi-fish' => ['Ikan Sashimi Lainnya', 'Spesies lain yang lazim disajikan sebagai sashimi, seperti kanpachi.'],
    ],

    'processing_methods' => [
        'fresh' => ['Segar', 'Didinginkan, tidak pernah dibekukan. Disimpan dalam es atau ruang pendingin.'],
        'frozen' => ['Beku', 'Dibekukan dan disimpan pada suhu penyimpanan dingin standar.'],
        'super-frozen' => ['Super Frozen', 'Pembekuan suhu ultra rendah yang dirancang menjaga tekstur, warna, dan kesegaran setelah dicairkan.'],
        'skinless' => ['Tanpa kulit', 'Kulit dibuang.'],
        'skin-on' => ['Berkulit', 'Kulit dibiarkan menempel pada daging.'],
        'boneless' => ['Tanpa tulang', 'Tulang dan tulang halus dibuang.'],
        'trimmed' => ['Dirapikan (trimmed)', 'Dirapikan dari garis darah, urat, dan daging gelap.'],
        'co-treated' => ['Perlakuan CO', 'Diberi perlakuan karbon monoksida untuk menjaga warna merah daging. Nyatakan perlakuan ini bila pasar tujuan mewajibkannya.'],
    ],

    'cuts' => [
        'whole-round' => ['Utuh / Round', 'Seluruh ikan, disiangi atau utuh (round), sebelum dipotong.'],
        'head' => ['Kepala', 'Bagian kepala ikan.'],
        'collar' => ['Collar', 'Bagian collar di belakang insang, populer untuk dipanggang.'],
        'loin' => ['Loin', 'Potongan panjang tanpa tulang dari punggung ikan.'],
        'back-loin' => ['Back Loin', 'Loin dari punggung bagian atas, lebih rendah lemak dibanding sisi perut.'],
        'belly' => ['Belly', 'Bagian perut ikan yang berlemak.'],
        'akami' => ['Akami', 'Daging merah tanpa lemak pada tuna, diambil dari punggung.'],
        'chutoro' => ['Chutoro', 'Bagian perut tuna dengan kadar lemak sedang, di antara akami dan otoro.'],
        'otoro' => ['Otoro', 'Bagian perut tuna yang paling berlemak dan paling marbling.'],
        'cheek' => ['Pipi', 'Daging pipi dari kepala.'],
        'tail' => ['Ekor', 'Bagian ekor.'],
        'fillet' => ['Fillet', 'Satu sisi ikan yang dilepas dari tulang punggung.'],
        'saku' => ['Saku', 'Balok persegi panjang dari loin atau fillet, siap diiris untuk sashimi dan sushi.'],
        'sashimi-block' => ['Sashimi Block', 'Balok yang sudah dirapikan untuk diiris menjadi sashimi.'],
        'steak' => ['Steak', 'Potongan porsi melintang dari loin atau badan ikan.'],
        'cube-poke' => ['Cube / Poke', 'Potongan dadu untuk poke, tartare, dan salad.'],
        'strip-meat' => ['Strip Meat', 'Daging yang dikerok atau dirapikan dari tulang dan sisa potongan, dipakai untuk roll dan olahan campuran.'],
    ],

    // Species names stay as they are in the trade; only the description is translated.
    'species' => [
        'yellowfin-tuna' => 'Tuna tropis yang cepat tumbuh dengan daging merah muda cerah yang padat dan rasa bersih yang ringan, banyak dipakai untuk sashimi, sushi, dan poke.',
        'bluefin-tuna' => 'Tuna terbesar dan paling dihargai untuk sushi, dengan daging merah pekat dan bagian perut yang marbling: akami, chutoro, dan otoro.',
        'bigeye-tuna' => 'Tuna berdaging merah pekat dengan kadar lemak sedikit lebih tinggi daripada yellowfin, populer untuk sashimi dan sushi.',
        'atlantic-salmon' => 'Salmon berlemak dengan daging oranye bertekstur lembut dan rasa manis ringan, tersedia sepanjang tahun dari budidaya.',
        'hamachi' => 'Japanese amberjack, disebut hamachi saat muda dan buri saat dewasa: daging kaya rasa, manis ringan, dengan kesan akhir yang bersih.',
        'tai' => 'Red sea bream dalam masakan Jepang: daging putih bening yang padat dengan rasa lembut dan sedikit manis, sering disajikan sebagai sashimi.',
        'spanish-mackerel' => 'Makerel besar yang gesit dengan daging merah muda pucat yang padat, lazim dipanggang, di-searing, atau disajikan sebagai sashimi bila sangat segar.',
        'red-snapper' => 'Kakap karang dengan daging putih kemerahan yang padat dan rasa ringan yang sedikit manis, dijual utuh dan sebagai fillet.',
        'kanpachi' => 'Greater amberjack, dihargai di sushi bar karena teksturnya yang padat renyah dan rasa bersih yang sedikit berlemak.',
    ],

    // cut slug => [typical usage, packaging, sentence ({s} = product name prefix)]
    'cut_info' => [
        'whole-round' => ['Pengolahan lanjutan, fillet, ritel, dan foodservice', 'Diglazing dan dibungkus, curah atau dalam karton', '{s} utuh, dipasok untuk pengolahan lanjutan, fillet, atau penyajian ikan utuh.'],
        'loin' => ['Sashimi, sushi, poke, tataki', 'Divakum satuan, master carton', 'Loin {s} yang dirapikan dari punggung ikan, untuk diiris menjadi sashimi dan sushi atau dipotong dadu untuk poke.'],
        'saku' => ['Sashimi, sushi', 'Balok divakum, master carton', 'Balok saku {s} berbentuk persegi panjang dari loin, siap diiris untuk sashimi dan sushi.'],
        'steak' => ['Grill, hidangan searing, layanan restoran', 'Porsi IQF, disisipi lembaran, master carton', 'Steak {s} potongan porsi untuk searing, grill, dan menu restoran.'],
        'cube-poke' => ['Poke, tartare, salad, rice bowl', 'Kantong divakum, master carton', 'Potongan dadu {s} untuk poke bowl, tartare, dan salad.'],
        'strip-meat' => ['Sushi roll, olahan spicy tuna, isian', 'Kantong divakum, master carton', 'Daging {s} yang dikerok dari tulang dan sisa potongan, dipakai untuk roll dan olahan campuran lainnya.'],
        'akami' => ['Sashimi, sushi', 'Balok divakum, master carton', 'Akami, daging merah tanpa lemak dari {s}, untuk sashimi dan sushi klasik.'],
        'chutoro' => ['Sashimi dan sushi premium', 'Balok divakum, master carton', 'Chutoro, bagian perut {s} berlemak sedang, di antara akami yang ramping dan otoro yang kaya.'],
        'otoro' => ['Sashimi dan sushi premium', 'Balok divakum, master carton', 'Otoro, bagian perut {s} yang paling berlemak dan paling marbling, untuk sashimi dan sushi premium.'],
        'fillet' => ['Sashimi, sushi, panggang, tumis', 'Layer pack atau divakum, master carton', 'Fillet {s} yang dilepas dari tulang punggung, untuk sashimi, sushi, dan hidangan matang.'],
        'belly' => ['Sashimi, sushi, panggang', 'Divakum, master carton', 'Belly {s}, bagian bawah ikan yang berlemak, untuk sushi, sashimi, dan panggang.'],
    ],

    'freezing' => ['fresh' => 'Segar', 'frozen' => 'Beku', 'super_frozen' => 'Super Frozen'],
    'temperature' => ['0 to 4°C' => '0 sampai 4°C', '-18°C or below' => '-18°C atau lebih dingin', '-60°C' => '-60°C'],
    'shelf_life' => [
        'fresh' => '5 sampai 7 hari pada 0 sampai 4°C',
        'frozen' => '12 bulan pada -18°C atau lebih dingin',
        'super_frozen' => '12 bulan pada -60°C',
    ],
    'grades' => ['Sashimi Grade' => 'Sashimi Grade', 'Sushi Grade' => 'Sushi Grade', 'Sashimi Suitable' => 'Layak untuk sashimi', 'General Seafood' => 'Seafood umum'],
    'spec_labels' => ['Cut' => 'Potongan', 'Processing' => 'Pengolahan', 'Storage' => 'Penyimpanan', 'Typical usage' => 'Penggunaan umum', 'Species' => 'Spesies', 'Packaging' => 'Kemasan', 'Shelf life' => 'Masa simpan', 'Grade' => 'Grade'],

    'texts' => [
        'reference_photo' => '(foto referensi)',
        'supplied' => 'Tersedia',
        'typical_uses' => 'Penggunaan umum',
        'agreed' => 'Kemasan dan spesifikasi dapat disepakati per pesanan.',
    ],
];
