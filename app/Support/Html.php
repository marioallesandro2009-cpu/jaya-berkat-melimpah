<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Output side of the admin's rich text: everything a RichEditor field saved is
 * rendered through Filament's sanitizer (Symfony HtmlSanitizer) before it reaches
 * a `{!! !!}` in a view, so a tampered save request cannot put a script on the site.
 */
final class Html
{
    public static function rich(?string $html): string
    {
        return filled($html) ? RichContentRenderer::make($html)->toHtml() : '';
    }

    /**
     * Plain text from a textarea -> escaped <p> paragraphs (blank line = new paragraph).
     * Nothing from the input is ever output as HTML.
     */
    public static function paragraphs(?string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/', trim((string) $text)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $p): string => trim($p))
            ->filter()
            ->map(fn (string $p): string => '<p>'.nl2br(e($p), false).'</p>')
            ->implode('');
    }
}
