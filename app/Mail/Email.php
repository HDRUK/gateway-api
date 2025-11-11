<?php

namespace App\Mail;

use CloudLogger;
use Config;
use App\Models\EmailTemplate;
use App\Exceptions\MailSendException;
use Exception;
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
        $this->fromAddress = $fromAddress ?? config('mail.from.address');
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
        try {
            $this->replaceBodyText();

            $response = Http::withBasicAuth(
                config('services.mjml.api_application_key'),
                config('services.mjml.api_key')
            )
            ->post(config('services.mjml.render_url'), [
                'mjml' => $this->template['body'],
            ]);


            if ($response->successful()) {
                return $response->json()['html'];
            }

            throw new MailSendException('Unable to contact MJML API - aborting');
        } catch (Exception $e) {
            CloudLogger::write([
                'action_type' => 'MJML',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new MailSendException('Error rendering MJML to HTML. Please check logs for details.');
        }
    }

    private function replaceBodyText(): void
    {
        foreach ($this->replacements as $k => $v) {
            $this->template['body'] = str_replace($k, $v, $this->template['body']);
        }

        if (isset($this->template['buttons'])) {
            $buttons = json_decode($this->template['buttons'], true);
            foreach ($buttons['replacements'] as $b) {
                $containsEnv = strpos($b['actual'], "config(");

                if ($containsEnv !== false) {
                    $start = $containsEnv + strlen("config(");
                    $end = strpos($b['actual'], ")", $start);
                    $subject = substr($b['actual'], $start, $end - $start);

                    $b['actual'] = str_replace("config(" . $subject . ")", config($subject), $b['actual']);
                }

                // In case of dynamic values within the 'actual' link we need to replace those
                // with anything incoming too. Such as TEAM_ID etc.
                foreach ($this->replacements as $k => $v) {
                    $b['actual'] = str_replace($k, $v, $b['actual']);
                }

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
