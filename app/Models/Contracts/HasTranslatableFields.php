<?php

namespace App\Models\Contracts;

/**
 * A model with texts per language (implemented by App\Models\Concerns\HasTranslations).
 */
interface HasTranslatableFields
{
    /** Review status of a translation (see translationState()). */
    public const STATE_EMPTY = 'empty';

    public const STATE_DRAFT = 'draft';

    public const STATE_REVIEWED = 'reviewed';

    public function translate(string $field, ?string $locale = null): ?string;

    public function translation(string $field, string $locale): ?string;

    public function translationState(string $field, string $locale): string;

    /**
     * Marks every filled translation in a language as reviewed (e.g. seeded texts).
     */
    public function markTranslationsReviewed(string $locale): static;

    /**
     * @return list<string>
     */
    public function missingTranslations(string $locale): array;

    /**
     * @return list<string>
     */
    public function draftTranslations(string $locale): array;
}
