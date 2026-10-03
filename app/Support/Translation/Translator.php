<?php

namespace App\Support\Translation;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Drafts a translation with the service set in .env (TRANSLATION_DRIVER = deepl, google or
 * anthropic, and TRANSLATION_API_KEY). The result is only a draft: the admin checks it and
 * marks it "Sudah dicek" before it appears on the site.
 *
 * Glossary terms (Pengaturan Situs › Terjemahan) are wrapped in tags the
 * service leaves untouched: a term without a fixed translation stays as it is,
 * a term with one is replaced by it.
 */
final class Translator
{
    public const DRIVERS = ['deepl', 'google', 'anthropic'];

    /** Language names for the Claude prompt. */
    private const LANGUAGES = ['en' => 'English', 'id' => 'Indonesian'];

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
            $translated = match ($driver) {
                'deepl' => self::deepl($marked, $from, $to),
                'anthropic' => self::anthropic($marked, $from, $to),
                default => self::google($marked, $from, $to),
            };
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

    /**
     * Claude (Anthropic Messages API). The text is XML-escaped and glossary terms are wrapped in
     * <span translate="no">, exactly as for Google, and the instructions keep both untouched.
     */
    private static function anthropic(string $text, string $from, string $to): string
    {
        $url = config('services.translation.url') ?: 'https://api.anthropic.com';
        $model = config('services.translation.model') ?: 'claude-haiku-4-5-20251001';
        $source = self::LANGUAGES[$from] ?? strtoupper($from);
        $target = self::LANGUAGES[$to] ?? strtoupper($to);

        $response = Http::timeout(40)
            ->withHeaders(['x-api-key' => (string) config('services.translation.key'), 'anthropic-version' => '2023-06-01'])
            ->post(rtrim((string) $url, '/').'/v1/messages', [
                'model' => $model,
                'max_tokens' => 2048,
                'temperature' => 0,
                'system' => "You translate website copy of a seafood processor and exporter from {$source} to {$target}. The input is XML-escaped text: keep every XML entity and every <span translate=\"no\">...</span> element exactly as written (the text inside the span stays untranslated), add no markup, and keep product names, codes, numbers and units unchanged. Reply with the translation only, nothing else.",
                'messages' => [['role' => 'user', 'content' => $text]],
            ]);

        if ($response->failed()) {
            throw new TranslationFailed('Claude menolak permintaan (HTTP '.$response->status().').');
        }

        $translated = trim((string) $response->json('content.0.text'));

        if ($translated === '') {
            throw new TranslationFailed('Claude tidak mengembalikan terjemahan.');
        }

        return $translated;
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
