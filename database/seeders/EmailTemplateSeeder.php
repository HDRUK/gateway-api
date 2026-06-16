<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateAssembler;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::truncate();

        $assembler = new EmailTemplateAssembler(__DIR__ . '/email-templates/_layouts');

        $placeholders = [
            '[[GCS_STORAGE_API_URI]]' => config('filesystems.disks.gcs_media.storage_api_uri'),
            '[[GCS_BUCKET]]'          => config('filesystems.disks.gcs_media.bucket'),
            '[[GATEWAY_URL]]'         => config('gateway.gateway_url'),
        ];

        foreach (glob(__DIR__ . '/email-templates/*.json') as $file) {
            $template = json_decode(file_get_contents($file), true);

            $mjml = $assembler->assemble($template);
            $mjml = str_replace(array_keys($placeholders), array_values($placeholders), $mjml);

            if (isset($template['buttons']) && is_array($template['buttons'])) {
                $template['buttons'] = json_encode($template['buttons']);
            }

            EmailTemplate::create([
                'identifier' => $template['identifier'],
                'subject'    => $template['subject'],
                'body'       => $mjml,
                'buttons'    => $template['buttons'] ?? null,
            ]);
        }
    }
}
