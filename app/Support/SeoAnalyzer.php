<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Yoast-style on-page SEO check for one blog post in one language. Pure function of
 * its input, used by the admin post form for a live score (see PostForm::analyzer()).
 *
 * Each check is [status, text] where status is "good" | "warn" | "bad" | "info";
 * good = 2 points, warn = 1, bad = 0, info = not scored. The score is the share of
 * earned points over the scored checks, 0-100.
 */
final class SeoAnalyzer
{
    private const TECHNICAL = 'Technical SEO (otomatis)';

    /**
     * @param  array{title?: ?string, slug?: ?string, excerpt?: ?string, content?: ?string, seo_title?: ?string, seo_description?: ?string, keyword?: ?string, has_cover?: bool}  $post
     * @return array{score: int, label: string, tone: string, minutes: int, groups: array<int, array{title: string, checks: array<int, array{0: string, 1: string}>}>}
     */
    public static function analyze(array $post): array
    {
        $title = trim((string) ($post['title'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        $seoTitle = trim((string) ($post['seo_title'] ?? '')) ?: $title;
        $ownDescription = trim((string) ($post['seo_description'] ?? ''));
        $description = $ownDescription ?: trim((string) ($post['excerpt'] ?? ''));
        $keyword = Str::lower(trim((string) ($post['keyword'] ?? '')));
        $html = (string) ($post['content'] ?? '');
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) preg_replace('/<\/(p|h[1-6]|li|blockquote)>/i', ' ', $html)), ENT_QUOTES | ENT_HTML5)));
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        $groups = [];

        $len = mb_strlen($seoTitle);
        $groups[] = ['title' => 'Title tag', 'checks' => [
            $len === 0 ? ['bad', 'Judul kosong.']
                : ($len < 40 || $len > 60
                    ? ['warn', "Panjang SEO title: {$len} karakter (ideal 50-60)."]
                    : ['good', "Panjang SEO title: {$len} karakter, sudah ideal."]),
        ]];

        $len = mb_strlen($description);
        $groups[] = ['title' => 'Meta description', 'checks' => [
            $len === 0 ? ['bad', 'Meta description belum diisi.']
                : ($len < 120 || $len > 160
                    ? ['warn', "Panjang meta description: {$len} karakter (ideal 150-160)."]
                    : ['good', "Panjang meta description: {$len} karakter, sudah ideal."]),
            ...($ownDescription !== '' || $len === 0 ? [] : [['info', 'Memakai ringkasan artikel karena deskripsi SEO kosong.']]),
        ]];

        $slugWords = $slug === '' ? 0 : count(array_filter(explode('-', $slug)));
        $groups[] = ['title' => 'URL slug', 'checks' => [
            $slug === '' ? ['bad', 'Slug belum ada.']
                : ($slugWords > 6 || mb_strlen($slug) > 60
                    ? ['warn', 'Slug terlalu panjang, usahakan maksimal 3-5 kata.']
                    : ['good', 'Slug pendek, rapi, dan deskriptif.']),
        ]];

        preg_match_all('/<h([2-3])\b[^>]*>(.*?)<\/h\1>/is', $html, $headingMatches);
        $headings = array_map(fn (string $h): string => Str::lower(trim(strip_tags($h))), $headingMatches[2]);
        $groups[] = ['title' => 'Struktur heading', 'checks' => [
            $headings === []
                ? ($wordCount > 200 ? ['warn', 'Belum ada subheading (H2/H3), tambahkan untuk memecah konten yang panjang.'] : ['info', 'Belum ada subheading (H2/H3).'])
                : ['good', count($headings).' subheading (H2/H3) ditemukan.'],
        ]];

        preg_match_all('/<img\b[^>]*>/i', $html, $images);
        $noAlt = count(array_filter($images[0], fn (string $tag): bool => ! preg_match('/\balt\s*=\s*"[^"]+"/i', $tag)));
        $groups[] = ['title' => 'Alt text gambar & sampul', 'checks' => [
            $images[0] === [] ? ['info', 'Tidak ada gambar di konten (opsional, tapi gambar dengan alt text membantu SEO dan aksesibilitas).']
                : ($noAlt > 0 ? ['warn', "{$noAlt} gambar belum punya alt text."] : ['good', 'Semua gambar punya alt text.']),
            ! empty($post['has_cover']) ? ['good', 'Foto sampul sudah dipilih.'] : ['warn', 'Foto sampul belum dipilih (dipakai untuk Open Graph).'],
        ]];

        preg_match_all('/<a\b[^>]*href\s*=\s*"([^"]*)"/i', $html, $linkMatches);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $internal = $external = 0;

        foreach ($linkMatches[1] as $href) {
            $host = parse_url($href, PHP_URL_HOST);

            if ($host === null || $host === $appHost) {
                $internal++;
            } else {
                $external++;
            }
        }

        $groups[] = ['title' => 'Internal linking', 'checks' => [
            $internal >= 2 ? ['good', "{$internal} internal link ditemukan."] : ['warn', "{$internal} internal link ditemukan (rekomendasi minimal 2)."],
        ]];
        $groups[] = ['title' => 'External linking', 'checks' => [
            $external >= 1 ? ['good', "{$external} external link ditemukan."] : ['warn', '0 external link ditemukan (rekomendasi minimal 1).'],
        ]];

        $paragraphs = array_filter(array_map(fn (string $p): string => trim(strip_tags($p)), preg_split('/<\/p>/i', $html) ?: []));
        $avgParagraph = $paragraphs === [] ? 0 : (int) round($wordCount / count($paragraphs));
        $sentences = max(1, count(array_filter(preg_split('/[.!?]+\s/u', $text) ?: [])));
        $avgSentence = $wordCount === 0 ? 0 : (int) round($wordCount / $sentences);
        $groups[] = ['title' => 'Readability', 'checks' => [
            $avgParagraph > 120 || $avgSentence > 25
                ? ['warn', "Rata-rata {$avgParagraph} kata/paragraf, {$avgSentence} kata/kalimat. Pendekkan agar mudah dibaca."]
                : ['good', "Rata-rata {$avgParagraph} kata/paragraf, {$avgSentence} kata/kalimat."],
            $wordCount >= 300
                ? ['good', "Panjang konten: {$wordCount} kata."]
                : ['warn', "Panjang konten: {$wordCount} kata (minimal disarankan 300 kata)."],
        ]];

        if ($keyword === '') {
            $groups[] = ['title' => 'Focus keyword', 'checks' => [['warn', 'Focus keyword belum diisi (tab SEO).']]];
        } else {
            $has = fn (string $haystack): bool => Str::contains(Str::lower($haystack), $keyword);
            $keywordWords = count(preg_split('/\s+/u', $keyword) ?: []);
            $density = $wordCount === 0 ? 0.0 : round(substr_count(Str::lower($text), $keyword) * $keywordWords / $wordCount * 100, 1);
            $groups[] = ['title' => 'Keyword density', 'checks' => [
                $density === 0.0 ? ['bad', 'Kepadatan keyword: 0%. Focus keyword tidak muncul di konten.']
                    : ($density < 0.5 || $density > 3 ? ['warn', "Kepadatan keyword: {$density}% (ideal 0,5-3%)."] : ['good', "Kepadatan keyword: {$density}%, sudah ideal."]),
            ]];
            $groups[] = ['title' => 'Focus keyword', 'checks' => [
                $has($seoTitle) ? ['good', 'Focus keyword muncul di SEO title.'] : ['bad', 'Focus keyword belum muncul di SEO title.'],
                $description === '' ? ['warn', 'Meta description belum diisi.']
                    : ($has($description) ? ['good', 'Focus keyword muncul di meta description.'] : ['warn', 'Focus keyword belum muncul di meta description.']),
                $has(str_replace('-', ' ', $slug)) ? ['good', 'Focus keyword muncul di slug/URL.'] : ['warn', 'Focus keyword belum muncul di slug/URL.'],
                $has(mb_substr($text, 0, 150)) ? ['good', 'Focus keyword muncul di 150 karakter pertama konten.'] : ['warn', 'Focus keyword sebaiknya muncul di 150 karakter pertama konten.'],
                $headings === [] ? ['info', 'Tidak ada subheading untuk dicek.']
                    : (collect($headings)->contains(fn (string $h): bool => $has($h)) ? ['good', 'Focus keyword muncul di subheading.'] : ['warn', 'Tambahkan focus keyword di salah satu subheading H2/H3.']),
            ]];
        }

        // Always on: canonical, Open Graph/Twitter and BlogPosting JSON-LD come from
        // App\Support\Seo and BlogStructuredData. Informational, not scored.
        $groups[] = ['title' => self::TECHNICAL, 'checks' => [
            ['good', 'Canonical URL self-referencing otomatis aktif di halaman publik.'],
            ['good', 'Open Graph & Twitter Card otomatis dari title, meta description, dan foto sampul.'],
            ['good', 'Schema markup Article (NewsArticle) otomatis tampil di halaman publik artikel.'],
        ]];

        $points = ['good' => 2, 'warn' => 1, 'bad' => 0];
        $earned = $max = 0;

        foreach ($groups as $group) {
            if ($group['title'] === self::TECHNICAL) {
                continue;
            }

            foreach ($group['checks'] as [$status]) {
                if (isset($points[$status])) {
                    $earned += $points[$status];
                    $max += 2;
                }
            }
        }

        $score = $max === 0 ? 0 : (int) round($earned / $max * 100);

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Bagus' : ($score >= 50 ? 'Cukup' : 'Kurang'),
            'tone' => $score >= 80 ? 'good' : ($score >= 50 ? 'warn' : 'bad'),
            'minutes' => max(1, (int) ceil($wordCount / 200)),
            'groups' => $groups,
        ];
    }
}
