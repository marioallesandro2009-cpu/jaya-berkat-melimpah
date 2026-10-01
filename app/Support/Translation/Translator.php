<?php

namespace App\Support\Translation;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Drafts a translation with the service set in .env (TRANSLATION_DRIVER and
 * TRANSLATION_API_KEY). The result is only a draft: the admin checks it and
 * marks it "Sudah dicek" before it appears on the site.
 *
 * Glossary terms (Pengaturan Situs › Terjemahan) are wrapped in tags the
 * service leaves untouched: a term without a fixed translation stays as it is,
 * a term with one is replaced by it.
 */
final class Translator
{
    public const DRIVERS = ['deepl', 'google'];

    public static function enabled(): bool
    {
        return in_array(config('services.translation.driver'), self::DRIVERS, true)
            && filled(config('services.translation.key'));
    }

    /**
     * @throws TranslationFailed
     */
    public static function draft(string $text, string $from, string $to): string
    {
        if (! self::enabled()) {
            throw new TranslationFailed('Layanan terjemahan belum dikonfigurasi.');
        }

        $driver = (string) config('services.translation.driver');
        $marked = self::protectTerms($text, $from, $to, self::glossary(), $driver);

        try {
            $translated = $driver === 'deepl'
                ? self::deepl($marked, $from, $to)
                : self::google($marked, $from, $to);
        } catch (TranslationFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TranslationFailed('Layanan terjemahan tidak merespons: '.$exception->getMessage(), previous: $exception);
        }

        return self::unprotect($translated);
    }

    /**
     * Escapes the text as markup and wraps glossary terms (longest first,
     * whole words, any case) in a tag the service does not translate.
     *
     * @param  list<array{en: string, id: string|null}>  $glossary
     */
    public static function protectTerms(string $text, string $from, string $to, array $glossary, string $driver): string
    {
        $replacements = [];

        foreach ($glossary as $entry) {
            $source = trim((string) ($entry[$from] ?? ''));

            if ($source !== '') {
                $target = trim((string) ($entry[$to] ?? ''));
                $replacements[mb_strtolower($source)] = $target !== '' ? $target : null;
            }
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        if ($replacements === []) {
            return $escaped;
        }

        $terms = array_keys($replacements);
        usort($terms, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $pattern = '/(?<![\p{L}\p{N}])('.implode('|', array_map(
            fn (string $term): string => preg_quote(htmlspecialchars($term, ENT_QUOTES | ENT_XML1, 'UTF-8'), '/'),
            $terms,
        )).')(?![\p{L}\p{N}])/iu';

        [$open, $close] = $driver === 'deepl' ? ['<keep>', '</keep>'] : ['<span translate="no">', '</span>'];

        return (string) preg_replace_callback($pattern, function (array $match) use ($replacements, $open, $close): string {
            $fixed = $replacements[mb_strtolower(html_entity_decode($match[1], ENT_QUOTES | ENT_XML1, 'UTF-8'))] ?? null;

            return $open.($fixed !== null ? htmlspecialchars($fixed, ENT_QUOTES | ENT_XML1, 'UTF-8') : $match[1]).$close;
        }, $escaped);
    }

    public static function unprotect(string $translated): string
    {
        $plain = (string) preg_replace('#</?keep>|<span translate="no">|</span>#', '', $translated);

        return trim(html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return list<array{en: string, id: string|null}>
     */
    private static function glossary(): array
    {
        return SiteSetting::current()->glossary();
    }

    private static function deepl(string $text, string $from, string $to): string
    {
        $key = (string) config('services.translation.key');
        $url = config('services.translation.url')
            ?: (str_ends_with($key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com');

        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
            ->asForm()
            ->post(rtrim((string) $url, '/').'/v2/translate', [
                'text' => $text,
                'source_lang' => strtoupper($from),
                'target_lang' => strtoupper($to),
                'tag_handling' => 'xml',
                'ignore_tags' => 'keep',
            ]);

        if ($response->failed()) {
            throw new TranslationFailed('DeepL menolak permintaan (HTTP '.$response->status().').');
        }

        return (string) $response->json('translations.0.text');
    }

    private static function google(string $text, string $from, string $to): string
    {
        $url = config('services.translation.url') ?: 'https://translation.googleapis.com';

        $response = Http::timeout(15)
            ->post(rtrim((string) $url, '/').'/language/translate/v2?key='.urlencode((string) config('services.translation.key')), [
                'q' => $text,
                'source' => $from,
                'target' => $to,
                'format' => 'html',
            ]);

        if ($response->failed()) {
            throw new TranslationFailed('Google Translate menolak permintaan (HTTP '.$response->status().').');
        }

        return (string) $response->json('data.translations.0.translatedText');
    }
}
