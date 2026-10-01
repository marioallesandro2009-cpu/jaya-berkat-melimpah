<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\Links;
use App\Support\SecurityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * #contact inquiry form: validate, save the lead, email the team after the response.
 *
 * The lead is saved BEFORE any email is attempted and a mail failure never reaches
 * the visitor (it only flags the lead as email_failed for the admin). Works as a
 * plain HTML POST (redirect back with a flash) and as fetch/JSON.
 */
class ContactController extends Controller
{
    public function __invoke(StoreContactMessageRequest $request): JsonResponse|RedirectResponse
    {
        $success = SiteSetting::current()->uiTexts(app()->getLocale())['contact_success'];

        $data = $request->validated();
        $name = $this->clean($data['product'] ?? null);
        $product = $name === null ? null : Product::query()->active()->get()
            ->first(fn (Product $p): bool => $p->translation('name', 'en') === $name);

        $message = ContactMessage::query()->create([
            'name' => $this->clean($data['name']),
            'email' => $data['email'],
            'phone' => $this->clean($data['phone'] ?? null),
            'company' => $this->clean($data['company'] ?? null),
            'country' => $this->clean($data['country'] ?? null),
            'product_id' => $product?->id,
            'product_title' => $name,
            'volume' => $this->clean($data['volume'] ?? null),
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        dispatch(fn () => self::notify($message))->afterResponse();

        return $this->respond($request, $success);
    }

    /**
     * Email every recipient (one message each, so one bad address cannot block the
     * others). Runs after the response; any failure only flags the lead.
     */
    public static function notify(ContactMessage $message): void
    {
        $recipients = SiteSetting::current()->contactRecipients();
        $failed = $recipients === [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (Throwable $e) {
                $failed = true;
                // Ids and the exception class only: no address, no SMTP text, no message content.
                SecurityLog::event('contact.email_failed', ['lead' => $message->id, 'error' => $e::class]);
            }
        }

        if ($failed) {
            $message->forceFill(['email_failed' => true])->save();
        }
    }

    private function respond(Request $request, string $text): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $text]);
        }

        return redirect(Links::section('contact'))->with('contact_status', $text);
    }

    /**
     * Single-line text without control characters (names go into email headers).
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }
}
