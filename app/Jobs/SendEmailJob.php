<?php

namespace App\Jobs;

use App\Mail\Email;
use App\Models\EmailTemplate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to = [];
    private $template = null;
    private $replacements = [];

    /**
     * Create a new job instance.
     */
    public function __construct(array $to, EmailTemplate $template, array $replacements)
    {
        $this->to = $to;
        $this->template = $template;
        $this->replacements = $replacements;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->to)
            ->send(new Email($this->template, $this->replacements));
    }
}
