<?php

namespace Database\Seeders;

use App\Models\PageSection;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * The headline copy of the home and company pages (English and Indonesian). A section
 * that already exists is left as the admin edited it; only its photo is attached when missing.
 */
class PageSectionSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        $en = fn (?string $en, ?string $id): ?array => $en === null ? null : ['en' => $en, 'id' => $id];

        // key => [eyebrow, title, body, body_extra, cta, image file, image alt]; each text is [en, id].
        $sections = [
            'hero' => [
                ['PT Jaya Berkat Melimpah · Fisheries & Seafood', 'PT Jaya Berkat Melimpah · Perikanan & Seafood'],
                ['From the largest archipelago to the global market.', 'Dari kepulauan terbesar ke pasar global.'],
                ['Sourcing, processing, and delivering quality seafood from the waters of the world\'s largest archipelago.', 'Mencari, mengolah, dan mengirimkan seafood berkualitas dari perairan kepulauan terbesar di dunia.'],
                null, null,
                ['hero-ocean', ['Open ocean waters across the Indonesian archipelago at first light', 'Perairan lepas di kepulauan Indonesia saat fajar']],
            ],
            'statement' => [
                null,
                ['From Indonesian waters to tables around the world.', 'Dari perairan Indonesia ke meja makan di seluruh dunia.'],
                ['PT Jaya Berkat Melimpah is an Indonesian fisheries and seafood company operating across the archipelago\'s coastal and deep-sea waters. We connect the natural abundance of Indonesian seas with the quality and consistency that global markets require — from sourcing through to distribution.', 'PT Jaya Berkat Melimpah adalah perusahaan perikanan dan seafood Indonesia yang beroperasi di perairan pesisir dan laut dalam nusantara. Kami menghubungkan kekayaan alam laut Indonesia dengan mutu dan konsistensi yang dibutuhkan pasar global — dari pengadaan hingga distribusi.'],
                null,
                ['Read our company profile', 'Baca profil perusahaan'],
                null,
            ],
            'origin' => [
                ['Origin', 'Asal'],
                ['Rooted in Indonesian waters, built for global trade.', 'Berakar di perairan Indonesia, dibangun untuk perdagangan global.'],
                ['Every stage of our process — from the moment fish leave the water to the moment they reach a buyer\'s dock — is built around consistency, traceability, and care.', 'Setiap tahap proses kami — dari saat ikan keluar dari air hingga tiba di dermaga pembeli — dibangun di atas konsistensi, ketertelusuran, dan kehati-hatian.'],
                ['It\'s an approach shaped by years spent working alongside Indonesia\'s fishing communities, and one we continue to refine as we grow.', 'Pendekatan ini dibentuk oleh tahun-tahun bekerja bersama komunitas nelayan Indonesia, dan terus kami sempurnakan seiring pertumbuhan kami.'],
                null,
                ['lautdalam', ['Deep-sea fishing waters in Indonesia', 'Perairan laut dalam tempat nelayan Indonesia menangkap ikan']],
            ],
            'quality' => [
                ['Quality', 'Mutu'],
                ['Held to a standard, not just a claim.', 'Dijaga dengan standar, bukan sekadar klaim.'],
                null, null, null,
                ['operations', ['PT Jaya Berkat Melimpah operations facility', 'Fasilitas operasional PT Jaya Berkat Melimpah']],
            ],
            'products' => [
                ['Products', 'Produk'],
                ['Seafood, sourced and presented with care.', 'Seafood yang dipilih dan disajikan dengan cermat.'],
                null, null, null, null,
            ],
            'chain' => [
                ['The journey', 'Perjalanan'],
                ['From the largest archipelago to the global market.', 'Dari kepulauan terbesar ke pasar global.'],
                null, null, null, null,
            ],
            'sustainability' => [
                ['Sustainability', 'Keberlanjutan'],
                ['Respect for the communities and waters we depend on.', 'Menghormati komunitas dan perairan yang kami andalkan.'],
                null, null, null,
                ['lautpasangsurut', ['Coastal tidal waters used for seafood sourcing', 'Perairan pasang surut pesisir tempat pengadaan seafood']],
            ],
            'markets' => [
                null,
                ['Reaching markets beyond the archipelago.', 'Menjangkau pasar di luar kepulauan.'],
                ['Our products reach seafood buyers across Asia and beyond, supported by a distribution network built for reliability at scale.', 'Produk kami menjangkau pembeli seafood di seluruh Asia dan sekitarnya, didukung jaringan distribusi yang dibangun untuk keandalan dalam skala besar.'],
                null, null, null,
            ],
            'partners' => [
                ['Partners', 'Partner'],
                ['Working with partners we trust.', 'Bekerja bersama mitra yang kami percaya.'],
                null, null, null, null,
            ],
            'locations' => [
                ['Locations', 'Lokasi'],
                ['Where to find us.', 'Di mana kami berada.'],
                null, null, null, null,
            ],
            'faq' => [
                ['FAQ', 'FAQ'],
                ['Frequently asked questions.', 'Pertanyaan yang sering diajukan.'],
                null, null, null, null,
            ],
            'news' => [
                ['News', 'Berita'],
                ['News & updates', 'Berita & pembaruan'],
                null, null,
                ['All news', 'Semua berita'],
                null,
            ],
            'contact' => [
                null,
                ['Looking for a seafood partner? Let\'s build a reliable supply relationship.', 'Mencari mitra seafood? Mari bangun hubungan pasokan yang andal.'],
                ['Tell us what you need — product, volume, and destination — and our team will reply with availability and next steps.', 'Ceritakan kebutuhan Anda — produk, volume, dan tujuan — dan tim kami akan membalas dengan ketersediaan dan langkah berikutnya.'],
                null, null, null,
            ],
            'company_header' => [
                ['Company Profile', 'Profil Perusahaan'],
                ['Built on Indonesian waters, working toward global standards.', 'Dibangun di perairan Indonesia, bekerja menuju standar global.'],
                ['An overview of PT Jaya Berkat Melimpah — our history, purpose, people, and the standards we hold ourselves to.', 'Gambaran tentang PT Jaya Berkat Melimpah — sejarah, tujuan, tim, dan standar yang kami pegang.'],
                null, null,
                ['lautdalam', ['Indonesian waters', 'Perairan Indonesia']],
            ],
            'company_overview' => [
                ['Who we are', 'Siapa kami'],
                ['An Indonesian fisheries and seafood company.', 'Perusahaan perikanan dan seafood Indonesia.'],
                ['PT Jaya Berkat Melimpah is built to connect the country\'s coastal and deep-sea waters with the standards today\'s global seafood buyers expect. We operate across sourcing, processing, and distribution, coordinating each stage to deliver consistent, reliable product to markets at home and abroad.', 'PT Jaya Berkat Melimpah dibangun untuk menghubungkan perairan pesisir dan laut dalam Indonesia dengan standar yang diharapkan pembeli seafood global saat ini. Kami beroperasi di bidang pengadaan, pengolahan, dan distribusi, mengoordinasikan setiap tahap untuk menghadirkan produk yang konsisten dan andal ke pasar dalam dan luar negeri.'],
                null, null,
                ['about', ['PT Jaya Berkat Melimpah overview', 'Gambaran PT Jaya Berkat Melimpah']],
            ],
            'company_history' => [['History', 'Sejarah'], ['How we got here', 'Perjalanan kami sampai di sini'], null, null, null, null],
            'company_vision' => [['Vision', 'Visi'], ['To be a trusted name connecting Indonesia\'s waters to seafood markets around the world.', 'Menjadi nama tepercaya yang menghubungkan perairan Indonesia dengan pasar seafood di seluruh dunia.'], null, null, null, null],
            'company_mission' => [['Mission', 'Misi'], ['To source, process, and deliver seafood with consistency, integrity, and respect for the communities and waters we depend on.', 'Mencari, mengolah, dan mengirimkan seafood dengan konsistensi, integritas, dan rasa hormat kepada komunitas dan perairan yang kami andalkan.'], null, null, null, null],
            'company_values' => [['Values', 'Nilai'], ['What guides our work', 'Yang memandu pekerjaan kami'], null, null, null, null],
            'company_leadership' => [['Leadership', 'Kepemimpinan'], ['The people behind the company', 'Orang-orang di balik perusahaan'], null, null, null, null],
            'company_operations' => [
                ['Operations', 'Operasional'],
                ['How we work', 'Cara kami bekerja'],
                ['Our day-to-day work spans sourcing, handling, and processing — coordinated across teams and facilities to keep product moving reliably from water to shipment.', 'Pekerjaan harian kami mencakup pengadaan, penanganan, dan pengolahan — dikoordinasikan antartim dan fasilitas agar produk terus bergerak andal dari perairan hingga pengiriman.'],
                null, null,
                ['operations', ['PT Jaya Berkat Melimpah operations', 'Operasional PT Jaya Berkat Melimpah']],
            ],
            'company_facilities' => [
                ['Where we operate', 'Lokasi operasi'],
                ['Along Indonesia\'s northern coast.', 'Di sepanjang pantai utara Indonesia.'],
                ['Our facilities are equipped to handle intake, processing, and cold storage, with primary operations based along Indonesia\'s northern coast.', 'Fasilitas kami dilengkapi untuk penerimaan, pengolahan, dan penyimpanan dingin, dengan operasi utama di sepanjang pantai utara Indonesia.'],
                null, null,
                ['lautpasangsurut', ['Coastal Indonesian waters near our operating region', 'Perairan pesisir Indonesia di dekat wilayah operasi kami']],
            ],
            'company_certifications' => [['Certifications', 'Sertifikasi'], ['Standards we hold', 'Standar yang kami pegang'], null, null, null, null],
            'company_closing' => [
                null,
                ['Looking for a seafood partner? Let\'s build a reliable supply relationship.', 'Mencari mitra seafood? Mari bangun hubungan pasokan yang andal.'],
                null, null,
                ['Contact our team', 'Hubungi tim kami'],
                null,
            ],
        ];

        $order = 0;

        foreach ($sections as $key => [$eyebrow, $title, $body, $extra, $cta, $image]) {
            $order++;
            $section = PageSection::query()->firstOrNew(['key' => $key]);

            if (! $section->exists) {
                $section->fill([
                    'eyebrow' => $en(...($eyebrow ?? [null, null])),
                    'title' => $en(...$title),
                    'body' => $en(...($body ?? [null, null])),
                    'body_extra' => $en(...($extra ?? [null, null])),
                    'cta_label' => $en(...($cta ?? [null, null])),
                    'image_alt' => $image ? $en(...$image[1]) : null,
                    'page' => str_starts_with($key, 'company_') ? 'company' : 'home',
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
                $section->save();
                $section->markTranslationsReviewed('id')->save();
            }

            if ($image) {
                $this->attachSeedImage($section, 'image', $image[0], 1920, 1200);
            }
        }
    }
}
