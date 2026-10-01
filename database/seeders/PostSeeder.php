<?php

namespace Database\Seeders;

use App\Models\Post;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * Three sample blog articles so the Blog link and page are visible from day one. Seeded only
 * while there are no articles; replace them in the admin (Blog) before launch.
 */
class PostSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        if (Post::query()->exists()) {
            return;
        }

        $posts = [
            [
                'image' => 'fish1',
                'en' => ['Sample: how we keep grouper fresh from sea to export', 'A sample article showing the cold chain from the boat to the container.', 'grouper cold chain'],
                'id' => ['Contoh: cara kami menjaga kerapu tetap segar hingga ekspor', 'Artikel contoh yang menunjukkan rantai dingin dari kapal hingga kontainer.', 'rantai dingin kerapu'],
            ],
            [
                'image' => 'fish2',
                'en' => ['Sample: what to ask your seafood supplier', 'A sample checklist for buyers comparing seafood exporters.', 'seafood supplier'],
                'id' => ['Contoh: pertanyaan penting untuk pemasok hasil laut', 'Contoh daftar periksa bagi pembeli yang membandingkan eksportir hasil laut.', 'pemasok hasil laut'],
            ],
            [
                'image' => 'operations',
                'en' => ['Sample: a day at our processing facility', 'A sample behind-the-scenes story from the processing floor.', 'seafood processing'],
                'id' => ['Contoh: sehari di fasilitas pengolahan kami', 'Contoh cerita di balik layar dari lantai pengolahan.', 'pengolahan hasil laut'],
            ],
        ];

        foreach ($posts as $i => $sample) {
            $post = Post::query()->create([
                'title' => ['en' => $sample['en'][0], 'id' => $sample['id'][0]],
                'excerpt' => ['en' => $sample['en'][1], 'id' => $sample['id'][1]],
                'content' => [
                    'en' => '<p>This is sample content. Replace it with your own article in the admin under Blog.</p><h2>Why it matters</h2><p>Write about what your buyers care about: freshness, traceability and reliable delivery.</p>',
                    'id' => '<p>Ini konten contoh. Ganti dengan artikel Anda sendiri di admin pada menu Blog.</p><h2>Mengapa penting</h2><p>Tulis hal yang dipedulikan pembeli: kesegaran, ketertelusuran, dan pengiriman yang andal.</p>',
                ],
                'focus_keyword' => ['en' => $sample['en'][2], 'id' => $sample['id'][2]],
                'status' => Post::PUBLISHED,
                'published_at' => now()->subDays(($i + 1) * 7),
                'is_active' => true,
            ]);

            $post->markTranslationsReviewed('id')->save();
            $this->attachSeedImage($post, 'cover', $sample['image'], 1600, 900);
        }
    }
}
