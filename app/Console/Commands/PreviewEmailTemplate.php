<?php

namespace App\Console\Commands;

use App\Services\EmailPreviewRenderer;
use App\Services\EmailTemplateAssembler;
use Exception;
use Illuminate\Console\Command;

class PreviewEmailTemplate extends Command
{
    protected $signature = 'email:preview
        {identifier : The template identifier (matches the JSON filename)}
        {--layout= : Override the layout (standard, new, none)}
        {--header-text= : Override the header/title text injected into the layout}';

    protected $description = 'Render an email template\'s MJML to HTML and open it in the browser';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $file = database_path("seeders/email-templates/{$identifier}.json");

        if (!file_exists($file)) {
            $this->error("No template file found at: {$file}");
            $this->line('Available templates:');
            foreach (glob(database_path('seeders/email-templates/*.json')) as $path) {
                $this->line('  ' . basename($path, '.json'));
            }
            return self::FAILURE;
        }

        $template = json_decode(file_get_contents($file), true);

        $validLayouts = ['standard', 'new', 'none'];

        if ($layoutOverride = $this->option('layout')) {
            if (!in_array($layoutOverride, $validLayouts, true)) {
                $this->error("Invalid layout '{$layoutOverride}'. Must be one of: " . implode(', ', $validLayouts));
                return self::FAILURE;
            }
            $this->line('  layout:      ' . ($template['layout'] ?? 'none') . ' → <fg=yellow>' . $layoutOverride . '</>');

            $template['layout'] = $layoutOverride;
        }

        if ($headerOverride = $this->option('header-text')) {
            $this->line("  header-text: " . ($template['header_text'] ?? '(none)') . " → <fg=yellow>{$headerOverride}</fg=yellow>");
            $template['header_text'] = $headerOverride;
        }

        $assembler = new EmailTemplateAssembler(database_path('seeders/email-templates/_layouts'));
        $body      = $assembler->assemble($template);

        // Resolve seeder-time config placeholders
        $body = str_replace(
            ['[[GCS_STORAGE_API_URI]]', '[[GCS_BUCKET]]', '[[GATEWAY_URL]]'],
            [
                config('filesystems.disks.gcs_media.storage_api_uri'),
                config('filesystems.disks.gcs_media.bucket'),
                config('gateway.gateway_url'),
            ],
            $body
        );

        // Resolve button URLs (mirrors the logic in App\Mail\Email)
        if (!empty($template['buttons']['replacements'])) {
            foreach ($template['buttons']['replacements'] as $replacement) {
                $actual = $replacement['actual'];

                if (str_contains($actual, 'config(')) {
                    $start   = strpos($actual, 'config(') + strlen('config(');
                    $end     = strpos($actual, ')', $start);
                    $key     = substr($actual, $start, $end - $start);
                    $actual  = str_replace("config({$key})", config($key) ?? "[[{$key}]]", $actual);
                }

                $body = str_replace($replacement['placeholder'], $actual, $body);
            }
        }

        $this->info("Calling MJML render API for '{$identifier}'...");

        try {
            $html = (new EmailPreviewRenderer())->renderHtml($body);
        } catch (Exception $e) {
            $this->error('MJML render failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $subject = $template['subject'] ?? $identifier;
        $html    = str_replace('<title>', "<title>{$subject} — ", $html);

        $tmpFile = sys_get_temp_dir() . "/email_preview_{$identifier}.html";

        file_put_contents($tmpFile, $html);

        $this->info("Rendered successfully. Opening: {$tmpFile}");

        // open is macOS; xdg-open covers Linux
        $opener = PHP_OS_FAMILY === 'Darwin' ? 'open' : 'xdg-open';
        exec("{$opener} " . escapeshellarg($tmpFile));

        return self::SUCCESS;
    }
}
