<?php

namespace Database\Seeders;

use App\Models\ChainStep;
use App\Models\Contracts\HasTranslatableFields;
use App\Models\Feature;
use App\Models\Stat;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * The journey stages, quality/sustainability/value blocks and the scale figures (the product catalogue is SeafoodCatalogSeeder).
 * Each table is seeded only while it is empty, so re-running never overwrites admin edits.
 * Stats are DUMMY figures (is_sample) until the company confirms the real ones.
 */
class CatalogSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        $this->stats();
        $this->chain();
        $this->features();
    }

    /**
     * @template T of Model&HasTranslatableFields
     *
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    private function create(string $model, array $attributes, int $order): Model
    {
        $record = $model::query()->create([...$attributes, 'sort_order' => $order, 'is_active' => true]);
        $record->markTranslationsReviewed('id')->save();

        return $record;
    }

    private function stats(): void
    {
        if (Stat::query()->exists()) {
            return;
        }

        $rows = [
            [['value' => 15, 'suffix' => '+', 'label' => ['en' => 'Years of experience', 'id' => 'Tahun pengalaman']]],
            [['value' => 20, 'suffix' => '+', 'label' => ['en' => 'Export markets', 'id' => 'Pasar ekspor']]],
            [['value' => 300, 'suffix' => '+', 'label' => ['en' => 'Team members', 'id' => 'Anggota tim']]],
            [['text_value' => ['en' => 'Jakarta', 'id' => 'Jakarta'], 'label' => ['en' => 'Headquartered in', 'id' => 'Berkantor pusat di']]],
        ];

        foreach ($rows as $i => [$attributes]) {
            $this->create(Stat::class, [...$attributes, 'is_sample' => true], $i + 1);
        }
    }

    private function chain(): void
    {
        if (ChainStep::query()->exists()) {
            return;
        }

        $rows = [
            ['lautdalam', ['Source', 'Sumber'],
                ['We source seafood from across Indonesia\'s fishing grounds, working within a supply network built on trust and consistency — from small-scale fishers to established regional partners.', 'Kami mengadakan seafood dari berbagai wilayah tangkap di Indonesia, dalam jaringan pasokan yang dibangun atas kepercayaan dan konsistensi — dari nelayan kecil hingga mitra regional yang mapan.']],
            ['about', ['Quality', 'Mutu'],
                ['Checked at intake. Quality checks are built into every stage before product leaves our facilities.', 'Diperiksa saat penerimaan. Pemeriksaan mutu ada di setiap tahap sebelum produk meninggalkan fasilitas kami.']],
            ['operations', ['Processing', 'Pengolahan'],
                ['Product is handled and prepared to meet the standards required for both domestic and international distribution.', 'Produk ditangani dan disiapkan sesuai standar yang dibutuhkan untuk distribusi dalam negeri maupun internasional.']],
            ['fish2', ['Cold chain', 'Rantai dingin'],
                ['Cold chain handling is maintained at every step, end to end.', 'Penanganan rantai dingin dijaga di setiap langkah, dari ujung ke ujung.']],
            ['hero-ocean', ['Distribution', 'Distribusi'],
                ['From our facilities to ports across the archipelago, we coordinate distribution so product reaches buyers reliably and on schedule.', 'Dari fasilitas kami ke pelabuhan di seluruh nusantara, kami mengoordinasikan distribusi agar produk sampai ke pembeli dengan andal dan tepat waktu.']],
            ['lautpasangsurut', ['Market', 'Pasar'],
                ['Our products reach seafood buyers across Asia and beyond, supported by a distribution network built for reliability at scale.', 'Produk kami menjangkau pembeli seafood di seluruh Asia dan sekitarnya, didukung jaringan distribusi yang dibangun untuk keandalan dalam skala besar.']],
        ];

        foreach ($rows as $i => [$image, $title, $body]) {
            $step = $this->create(ChainStep::class, [
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'body' => ['en' => $body[0], 'id' => $body[1]],
            ], $i + 1);

            $this->attachSeedImage($step, 'image', $image, 1600, 1000);
        }
    }

    private function features(): void
    {
        if (Feature::query()->exists()) {
            return;
        }

        $rows = [
            [Feature::QUALITY, ['Quality control', 'Pengendalian mutu'],
                ['Every batch is inspected and handled under strict protocols, from intake through to final packing, to meet the standards our international buyers expect.', 'Setiap batch diperiksa dan ditangani dengan protokol ketat, dari penerimaan hingga pengemasan akhir, sesuai standar yang diharapkan pembeli internasional kami.']],
            [Feature::QUALITY, ['Day-to-day, on the ground', 'Sehari-hari, di lapangan'],
                ['Our operations span sourcing, handling, and preparing seafood for distribution — coordinated across facilities and fishing grounds to keep product moving efficiently from water to market.', 'Operasi kami mencakup pengadaan, penanganan, dan penyiapan seafood untuk distribusi — dikoordinasikan antarfasilitas dan wilayah tangkap agar produk terus bergerak efisien dari perairan ke pasar.']],
            [Feature::CHECKPOINT, ['Intake inspection', 'Pemeriksaan saat penerimaan'], null],
            [Feature::CHECKPOINT, ['Handling protocols', 'Protokol penanganan'], null],
            [Feature::CHECKPOINT, ['Final packing', 'Pengemasan akhir'], null],
            [Feature::SUSTAINABILITY, ['Sustainable sourcing', 'Pengadaan berkelanjutan'],
                ['We work to source responsibly, respecting seasonal patterns and operating within Indonesia\'s regulatory framework for sustainable fisheries.', 'Kami berupaya mengadakan secara bertanggung jawab, menghormati pola musim, dan beroperasi dalam kerangka regulasi perikanan berkelanjutan Indonesia.']],
            [Feature::SUSTAINABILITY, ['Community & fishers', 'Komunitas & nelayan'],
                ['Our supply network is built on long-standing relationships with local fishers and fishing communities across the archipelago.', 'Jaringan pasokan kami dibangun atas hubungan jangka panjang dengan nelayan lokal dan komunitas nelayan di seluruh nusantara.']],
            [Feature::VALUE, ['Integrity', 'Integritas'],
                ['We operate honestly and transparently — with our partners, our people, and the communities we work alongside.', 'Kami bekerja dengan jujur dan transparan — bersama mitra, tim, dan komunitas tempat kami bekerja.']],
            [Feature::VALUE, ['Reliability', 'Keandalan'],
                ['Buyers can count on consistent quality and dependable delivery, shipment after shipment.', 'Pembeli dapat mengandalkan mutu yang konsisten dan pengiriman yang dapat diandalkan, pengiriman demi pengiriman.']],
            [Feature::VALUE, ['Sustainability', 'Keberlanjutan'],
                ['We aim to source responsibly, respecting the long-term health of Indonesia\'s fisheries.', 'Kami berupaya mengadakan secara bertanggung jawab, menghormati kesehatan jangka panjang perikanan Indonesia.']],
            [Feature::VALUE, ['Partnership', 'Kemitraan'],
                ['We build long-term relationships with fishers, processors, and buyers alike, rather than one-off transactions.', 'Kami membangun hubungan jangka panjang dengan nelayan, pengolah, dan pembeli, bukan sekadar transaksi sekali jalan.']],
        ];

        foreach ($rows as $i => [$group, $title, $body]) {
            $this->create(Feature::class, [
                'group' => $group,
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'body' => $body ? ['en' => $body[0], 'id' => $body[1]] : null,
            ], $i + 1);
        }
    }
}
