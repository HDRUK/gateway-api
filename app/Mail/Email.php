<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Exceptions\MailSendException;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;

class Email extends Mailable
{
    use Queueable;
    use SerializesModels;

    private $template = null;
    private $replacements = [];
    public $subject = '';
    private $fromAddress = '';

    /**
     * Create a new message instance.
     */
    public function __construct(EmailTemplate $template, array $replacements, $fromAddress = null)
    {

        $this->template = $template;
        $this->replacements = $replacements;
        $this->subject = $this->template['subject'];
        $this->fromAddress = $fromAddress ?? env('MAIL_FROM_ADDRESS', 'noreply@healthdatagateway.org');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $this->replaceSubjectText();

        return new Envelope(
            from: new Address($this->fromAddress),
            subject: $this->subject,
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

        $response = Http::withBasicAuth(
            env('MJML_API_APPLICATION_KEY', ''),
            env('MJML_API_KEY', '')
        )
            ->post(env('MJML_RENDER_URL', ''), [
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

        if (isset($this->template['buttons'])) {
            $buttons = json_decode($this->template['buttons'], true);
            foreach ($buttons['replacements'] as $b) {
                $this->template['body'] = str_replace($b['placeholder'], $b['actual'], $this->template['body']);
            }
        }
    }

    private function replaceSubjectText(): void
    {
        foreach ($this->replacements as $k => $v) {
            $this->subject = str_replace($k, $v, $this->subject);
        }
    }
}
