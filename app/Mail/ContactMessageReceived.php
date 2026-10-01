<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Lead notification for the team. From = MAIL_FROM_ADDRESS (set in .env, not editable
 * in the admin), Reply-To = the visitor, so "Reply" answers the prospect directly.
 * The subject follows the visitor's language (Kontak tab); the body is always Indonesian.
 */
class ContactMessageReceived extends Mailable
{
    public function __construct(public ContactMessage $lead)
    {
        $this->locale('id');
    }

    public function envelope(): Envelope
    {
        $subject = SiteSetting::current()->uiTexts($this->lead->locale)['contact_email_subject'];

        return new Envelope(
            replyTo: [new Address($this->lead->email, $this->lead->name)],
            subject: str_replace(':name', $this->lead->name, $subject),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-message',
            with: ['lead' => $this->lead, 'localeName' => strtoupper($this->lead->locale)],
        );
    }
}
