<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::truncate();

        $placeholders = [
            '[[GCS_STORAGE_API_URI]]' => config('filesystems.disks.gcs_media.storage_api_uri'),
            '[[GCS_BUCKET]]'          => config('filesystems.disks.gcs_media.bucket'),
            '[[GATEWAY_URL]]'         => config('gateway.gateway_url'),
        ];

        foreach (glob(__DIR__ . '/email-templates/*.json') as $file) {
            $template = json_decode(file_get_contents($file), true);

            $template['body'] = str_replace(
                array_keys($placeholders),
                array_values($placeholders),
                $template['body']
            );

            if (isset($template['buttons']) && is_array($template['buttons'])) {
                $template['buttons'] = json_encode($template['buttons']);
            }

            EmailTemplate::create($template);
        }
    }
}
