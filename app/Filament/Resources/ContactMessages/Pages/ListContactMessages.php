<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\SiteSetting;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    /**
     * Warns while no recipient list is set (notifications then fall back to the public email).
     */
    public function getSubheading(): string|Htmlable|null
    {
        $settings = SiteSetting::current();

        if (! $settings->usesFallbackRecipient()) {
            return null;
        }

        return new HtmlString(view('filament.contact-recipients-notice', ['empty' => true, 'fallback' => $settings->email])->render());
    }
}
