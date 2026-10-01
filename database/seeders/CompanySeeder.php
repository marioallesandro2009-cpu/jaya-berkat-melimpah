<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Contracts\HasTranslatableFields;
use App\Models\Leader;
use App\Models\TimelineItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Company page content: history, leadership and certifications. EVERYTHING here is DUMMY
 * (is_sample) example content: confirm or replace each row in the admin before launch,
 * and never publish a certification the company does not hold. Seeded only while empty.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $this->timeline();
        $this->leaders();
        $this->certifications();
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
        $record = $model::query()->create([...$attributes, 'is_sample' => true, 'sort_order' => $order, 'is_active' => true]);
        $record->markTranslationsReviewed('id')->save();

        return $record;
    }

    private function timeline(): void
    {
        if (TimelineItem::query()->exists()) {
            return;
        }

        $rows = [
            [['2011', '2011'], ['Company founded', 'Perusahaan didirikan'],
                ['PT Jaya Berkat Melimpah began operations along the northern coast of Java, sourcing seafood directly from local fishing communities.', 'PT Jaya Berkat Melimpah memulai operasi di pantai utara Jawa, mengadakan seafood langsung dari komunitas nelayan setempat.']],
            [['2016', '2016'], ['Expansion into processing', 'Perluasan ke pengolahan'],
                ['We invested in processing capacity to prepare product for both domestic distribution and early export shipments.', 'Kami berinvestasi pada kapasitas pengolahan untuk menyiapkan produk bagi distribusi dalam negeri dan pengiriman ekspor awal.']],
            [['Today', 'Hari ini'], ['Operating across the archipelago', 'Beroperasi di seluruh nusantara'],
                ['Our sourcing and distribution network now spans multiple regions across Indonesia, serving buyers at home and abroad.', 'Jaringan pengadaan dan distribusi kami kini mencakup banyak wilayah di Indonesia, melayani pembeli di dalam dan luar negeri.']],
        ];

        foreach ($rows as $i => [$year, $title, $body]) {
            $this->create(TimelineItem::class, [
                'year_label' => ['en' => $year[0], 'id' => $year[1]],
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'body' => ['en' => $body[0], 'id' => $body[1]],
            ], $i + 1);
        }
    }

    private function leaders(): void
    {
        if (Leader::query()->exists()) {
            return;
        }

        $rows = [
            ['Budi Santoso', ['Chief Executive Officer', 'Direktur Utama']],
            ['Siti Rahayu', ['Director of Operations', 'Direktur Operasional']],
            ['Andi Wijaya', ['Director of Export & Trade', 'Direktur Ekspor & Perdagangan']],
        ];

        foreach ($rows as $i => [$name, $role]) {
            $this->create(Leader::class, ['name' => $name, 'role' => ['en' => $role[0], 'id' => $role[1]]], $i + 1);
        }
    }

    private function certifications(): void
    {
        if (Certification::query()->exists()) {
            return;
        }

        foreach (['HACCP', 'ISO 22000:2018', 'Halal Certification'] as $i => $name) {
            $this->create(Certification::class, ['name' => $name, 'status_label' => ['en' => 'Certified', 'id' => 'Bersertifikat']], $i + 1);
        }
    }
}
