<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\EmailTemplate;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $emailTemplates = EmailTemplate::all();
        foreach ($emailTemplates as $template) {
            if (isset($template['buttons'])) {
                $template['buttons'] = str_replace("env(GATEWAY_URL)", "config(gateway.gateway_url)", $template['buttons']);
                $template->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $emailTemplates = EmailTemplate::all();
        foreach ($emailTemplates as $template) {
            if (isset($template['buttons'])) {
                $template['buttons'] = str_replace("config(gateway.gateway_url)", "env(GATEWAY_URL)", $template['buttons']);
                $template->save();
            }
        }
    }
};
