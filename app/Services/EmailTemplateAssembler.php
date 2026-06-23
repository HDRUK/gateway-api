<?php

namespace App\Services;

class EmailTemplateAssembler
{
    /** @var array<string, string> */
    private array $layouts = [];

    public function __construct(private readonly string $layoutsDir)
    {
        foreach (['standard', 'new'] as $name) {
            $path = "{$this->layoutsDir}/{$name}.mjml";
            if (file_exists($path)) {
                $this->layouts[$name] = file_get_contents($path);
            }
        }
    }

    /**
     * Combine a template's unique body content with its layout shell.
     *
     * @param array{layout?: string, header_text?: string, body: string} $template
     */
    public function assemble(array $template): string
    {
        $layout = $template['layout'] ?? 'none';

        if ($layout !== 'none' && isset($this->layouts[$layout])) {
            $mjml = str_replace('[[HEADER_TEXT]]', $template['header_text'] ?? '', $this->layouts[$layout]);
            return str_replace('[[CONTENT]]', $template['body'], $mjml);
        }

        return $template['body'];
    }
}
