<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Exceptions\MailSendException;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;

class Email extends Mailable
{
    use Queueable, SerializesModels;

    public $to = [];
    private $template = null;
    private $replacements = [];

    /**
     * Create a new message instance.
     */
    public function __construct(array $to, EmailTemplate $template, array $replacements)
    {
        $this->to = $to;
        $this->template = $template;
        $this->replacements = $replacements;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@healthdatagateway.org'),
            subject: $this->template->subject,
        );
    }

    /**
     * Get the message content by building the mail.
     */
    public function build()
    {
        return $this->html($this->mjmlToHtml());
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function mjmlToHtml(): string
    {
        $this->replaceBodyText();

        $response = Http::withBasicAuth(env('MJML_API_APPLICATION_KEY'),
            env('MJML_API_KEY'))
            ->post(env('MJML_RENDER_URL'), [
                'mjml' => $this->template['body'],
            ]);

        if ($response->successful()) {
            return $response->json()['html'];
        }

        throw new MailSendException('unable to contact mjml api - aborting');
    }

    private function replaceBodyText(): void
    {
        foreach ($this->replacements as $k => $v) {
            $this->template['body'] = str_replace($k, $v, $this->template['body']);
        }
    }
}
