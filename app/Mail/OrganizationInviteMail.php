<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Organization $organization,
        public readonly string $acceptUrl,
        public readonly string $declineUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->organization->name} on GitCodeGud",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-invite',
            with: [
                'organization' => $this->organization,
                'acceptUrl'    => $this->acceptUrl,
                'declineUrl'   => $this->declineUrl,
            ],
        );
    }
}
