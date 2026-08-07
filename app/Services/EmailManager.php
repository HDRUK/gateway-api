<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;

class EmailManager
{
    /**
     * Single choke point for outbound templated email. Looks up the template
     * by identifier, refuses to send if missing or disabled (email_templates.enabled),
     * then dispatches SendEmailJob as before.
     *
     * @param array $to ['to' => ['email' => ..., 'name' => ...]]
     * @param array $replacements ['[[PLACEHOLDER]]' => value, ...]
     */
    public function send(string $identifier, array $to, array $replacements = [], ?string $fromAddress = null): bool
    {
        $template = EmailTemplate::where('identifier', $identifier)->first();

        if (is_null($template)) {
            Log::warning('EmailManager: email template not found, email not sent', [
                'identifier' => $identifier,
            ]);
            return false;
        }

        if (!$template->enabled) {
            Log::warning('EmailManager: email template disabled, email not sent', [
                'identifier' => $identifier,
            ]);
            return false;
        }

        SendEmailJob::dispatch($to, $template, $replacements, $fromAddress);
        return true;
    }
}
