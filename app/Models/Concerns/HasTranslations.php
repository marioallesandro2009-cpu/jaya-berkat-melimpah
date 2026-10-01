<?php

namespace App\Models\Concerns;

use App\Support\Locales;

/**
 * Text fields stored per language as JSON: {"en": "...", "id": "..."}.
 * The model lists them in TRANSLATABLE.
 *
 * Every translation (a language other than the default, English) has a review
 * status in translation_status ({"id": {"title": "draft" | "reviewed"}}): only a
 * reviewed translation is shown on the site; an empty or draft one shows the
 * English text instead.
 *
 * Assigning a plain string sets the default-language version only. The model
 * implements AppModelsContractsHasTranslatableFields (status constants).
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        $this->mergeCasts([
            ...array_fill_keys(static::TRANSLATABLE, 'json:unicode'),
            'translation_status' => 'json:unicode',
        ]);
    }

    /**
     * The text shown on the site in a language (current language by default):
     * the reviewed translation, else the default-language text, else null.
     */
    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale ??= Locales::current();

        if ($locale !== Locales::default() && $this->translationState($field, $locale) === self::STATE_REVIEWED) {
            return $this->translation($field, $locale);
        }

        return $this->translation($field, Locales::default());
    }

    /**
     * The text in exactly this language (no fallback, any status), or null when empty.
     */
    public function translation(string $field, string $locale): ?string
    {
        $values = $this->getAttribute($field);
        $text = trim((string) (is_array($values) ? ($values[$locale] ?? '') : ''));

        return $text !== '' ? $text : null;
    }

    /**
     * empty, draft (filled but not checked yet) or reviewed. The default language
     * has no review step: filled = reviewed.
     */
    public function translationState(string $field, string $locale): string
    {
        if ($this->translation($field, $locale) === null) {
            return self::STATE_EMPTY;
        }

        if ($locale === Locales::default()) {
            return self::STATE_REVIEWED;
        }

        $status = $this->getAttribute('translation_status');

        return is_array($status) && ($status[$locale][$field] ?? null) === self::STATE_REVIEWED
            ? self::STATE_REVIEWED
            : self::STATE_DRAFT;
    }

    /**
     * Fields that have a default-language text but none in this language.
     *
     * @return list<string>
     */
    public function missingTranslations(string $locale): array
    {
        return $this->fieldsInState($locale, self::STATE_EMPTY);
    }

    /**
     * Fields whose translation is filled but not checked yet.
     *
     * @return list<string>
     */
    public function draftTranslations(string $locale): array
    {
        return $this->fieldsInState($locale, self::STATE_DRAFT);
    }

    /**
     * Marks every filled translation in a language as reviewed (e.g. seeded texts).
     */
    public function markTranslationsReviewed(string $locale): static
    {
        $status = is_array($this->translation_status) ? $this->translation_status : [];

        foreach (static::TRANSLATABLE as $field) {
            if ($this->translation($field, $locale) !== null) {
                $status[$locale][$field] = self::STATE_REVIEWED;
            }
        }

        $this->translation_status = $status;

        return $this;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value) && in_array($key, static::TRANSLATABLE, true)) {
            $value = [Locales::default() => $value];
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @return list<string>
     */
    private function fieldsInState(string $locale, string $state): array
    {
        return array_values(array_filter(
            static::TRANSLATABLE,
            fn (string $field): bool => $this->translation($field, Locales::default()) !== null
                && $this->translationState($field, $locale) === $state,
        ));
    }
}
