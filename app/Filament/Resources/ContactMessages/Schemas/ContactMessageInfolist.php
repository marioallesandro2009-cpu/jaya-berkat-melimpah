<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Pengirim')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Nama'),
                    TextEntry::make('email')->label('Email')->copyable()->url(fn (ContactMessage $record): string => 'mailto:'.$record->email),
                    TextEntry::make('phone')->label('Telepon')->default('-'),
                    TextEntry::make('company')->label('Perusahaan')->default('-'),
                    TextEntry::make('country')->label('Negara')->default('-'),
                    TextEntry::make('product_title')->label('Produk diminati')->default('-'),
                    TextEntry::make('volume')->label('Perkiraan volume')->default('-'),
                    TextEntry::make('locale')->label('Bahasa asal')->formatStateUsing(fn (string $state): string => strtoupper($state)),
                ]),
            Section::make('Pesan')
                ->schema([
                    TextEntry::make('message')->hiddenLabel()->html()->default('-')->formatStateUsing(fn (?string $state): string => nl2br(e((string) $state))),
                ]),
            Section::make('Status & data teknis')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => ContactMessage::STATUSES[$state] ?? $state)
                        ->color(fn (string $state): string => $state === ContactMessage::NEW ? 'warning' : 'gray'),
                    TextEntry::make('email_failed')
                        ->label('Notifikasi email')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Email gagal terkirim' : 'Terkirim')
                        ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                    TextEntry::make('created_at')->label('Diterima (WIB)')->dateTime('d M Y H:i', 'Asia/Jakarta'),
                    TextEntry::make('ip')->label('IP (data pribadi)')->default('-'),
                    TextEntry::make('user_agent')->label('Browser (data pribadi)')->default('-')->columnSpan(2),
                ]),
        ]);
    }
}
