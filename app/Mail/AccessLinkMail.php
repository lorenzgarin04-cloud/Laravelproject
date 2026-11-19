<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $url,
        public int $hours = 72,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->user->isAdmin()
                ? 'Set up your De Guzman Drugmart admin account'
                : 'Your De Guzman Drugmart access link'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.access-link');
    }
}
