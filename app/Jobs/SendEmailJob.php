<?php

namespace App\Jobs;

use App\Mail\Email;
use App\Models\EmailTemplate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $to = [];
    private $template = null;
    private $replacements = [];
    private $fromAddress = '';

    /**
     * Create a new job instance.
     */
    public function __construct(array $to, EmailTemplate $template, array $replacements, $fromAddress = null)
    {
        $this->to = $to;
        $this->template = $template;
        $this->replacements = $replacements;
        $this->fromAddress = $fromAddress;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->to)
           ->send(new Email($this->template, $this->replacements, $this->fromAddress));
    }

    public function tags(): array
    {

        return [
            'send_email_job',
            'to:' . json_encode($this->to),
        ];
    }
}
