<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A lead sent through the #contact form. Saved first, emailed second (see
 * App\Http\Controllers\ContactController); "email_failed" marks a lead whose
 * notification email could not be sent. ip / user_agent are personal data: never
 * shown on the public site, only on the admin detail page.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $country
 * @property int|null $product_id
 * @property string|null $product_title
 * @property string|null $volume
 * @property string $message
 * @property string $locale
 * @property string $status
 * @property bool $email_failed
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 */
#[Fillable(['name', 'email', 'phone', 'company', 'country', 'product_id', 'product_title', 'volume', 'message', 'locale', 'status', 'email_failed', 'ip', 'user_agent'])]
class ContactMessage extends Model
{
    public const NEW = 'new';

    public const READ = 'read';

    public const STATUSES = [self::NEW => 'Baru', self::READ => 'Dibaca'];

    protected function casts(): array
    {
        return ['email_failed' => 'boolean'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->where('status', self::NEW);
    }

    public function markRead(): void
    {
        if ($this->status !== self::READ) {
            $this->update(['status' => self::READ]);
        }
    }
}
