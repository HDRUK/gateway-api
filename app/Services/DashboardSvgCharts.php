<?php

namespace App\Services;

class DashboardSvgCharts
{
    // Canvas dimensions
    private int $width  = 520;
    private int $height = 240;

    // Padding around the plot area
    private int $padTop    = 20;
    private int $padRight  = 20;
    private int $padBottom = 30;
    private int $padLeft   = 62;

    // Colours
    private string $colorGrid        = '#e2e6ea';
    private string $colorAccent      = '#2d5a8e';
    private string $colorAccentMed   = '#4a7ab5';
    private string $colorAccentLight = '#a8c4e0';
    private string $colorMuted       = '#6b7280';
    private string $colorText        = '#1a1d23';
    private string $colorTrack       = '#f0f2f5';

    // ─────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────

    /**
     * Render a line chart and return an SVG string.
     *
     * @param array{labels: string[], values: int[]} $data
     */
    public function renderLineChart(array $data): string
    {
        $labels = $data['labels'];
        $values = $data['values'];
        $count  = count($values);

        $plotX = $this->padLeft;
        $plotY = $this->padTop;
        $plotW = $this->width  - $this->padLeft - $this->padRight;
        $plotH = $this->height - $this->padTop  - $this->padBottom;

        $minVal = 0;
        $maxVal = $this->niceMax($values);

        $svg = $this->openSvg($this->width, $this->height);

        // Gradient fill definition
        $svg .= '<defs>';
        $svg .= '<linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">';
        $svg .= '<stop offset="0%" stop-color="' . $this->colorAccent . '" stop-opacity="0.13"/>';
        $svg .= '<stop offset="100%" stop-color="' . $this->colorAccent . '" stop-opacity="0.01"/>';
        $svg .= '</linearGradient>';
        $svg .= '</defs>';

        // Y-axis grid lines + labels
        $ySteps = 4;
        for ($i = 0; $i <= $ySteps; $i++) {
            $val = (int) ($minVal + ($maxVal - $minVal) * ($i / $ySteps));
            $y   = round($plotY + $plotH - ($plotH * ($i / $ySteps)));
            $svg .= $this->hLine($plotX, $y, $plotX + $plotW, $this->colorGrid, '4,4');
            $svg .= $this->text((string)$val, $plotX - 6, $y + 4, $this->colorMuted, 10, 'end');
        }

        // Compute pixel coords
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $x        = $plotX + ($plotW / ($count - 1)) * $i;
            $y        = $plotY + $plotH - ($plotH * (($values[$i] - $minVal) / ($maxVal - $minVal)));
            $points[] = ['x' => round($x, 2), 'y' => round($y, 2), 'l' => $labels[$i]];
        }

        // Smooth cubic bezier path
        $pathD = $this->smoothPath($points);

        // Fill area
        $first = $points[0];
        $last  = $points[$count - 1];
        $fillD = $pathD . " L{$last['x']}," . ($plotY + $plotH) . " L{$first['x']}," . ($plotY + $plotH) . ' Z';
        $svg .= '<path d="' . $fillD . '" fill="url(#lineGrad)" stroke="none"/>';

        // Line
        $svg .= '<path d="' . $pathD . '" fill="none" stroke="' . $this->colorAccent . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';

        // X labels
        foreach ($points as $p) {
            $svg .= $this->text($p['l'], $p['x'], $plotY + $plotH + 20, $this->colorMuted, 10, 'middle');
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Render a horizontal bar chart and return an SVG string.
     *
     * @param array{labels: string[], values: int[]} $data
     */
    public function renderBarChart(array $data): string
    {
        $labels    = $data['labels'];
        $values    = $data['values'];
        $count     = count($values);
        $barHeight = 22;
        $barGap    = 10;
        $chartH    = $this->padTop + ($count * ($barHeight + $barGap)) + $this->padBottom;

        $plotX  = $this->padLeft;
        $plotY  = $this->padTop;
        $plotW  = $this->width - $this->padLeft - $this->padRight;
        $maxVal = max($values);

        $svg = $this->openSvg($this->width, $chartH);

        // X-axis grid lines + labels
        $xSteps = 4;
        for ($i = 0; $i <= $xSteps; $i++) {
            $x   = round($plotX + ($plotW / $xSteps) * $i);
            $val = (int) (($maxVal / $xSteps) * $i);
            $svg .= $this->vLine($x, $plotY, $plotY + ($count * ($barHeight + $barGap)), $this->colorGrid, '4,4');
            $svg .= $this->text((string)$val, $x, $plotY + ($count * ($barHeight + $barGap)) + 16, $this->colorMuted, 10, 'middle');
        }

        // Bars
        foreach ($values as $i => $val) {
            $y     = $plotY + $i * ($barHeight + $barGap);
            $barW  = round($plotW * ($val / $maxVal));
            $ratio = $val / $maxVal;
            $color = $ratio > 0.9 ? $this->colorAccent : ($ratio > 0.6 ? $this->colorAccentMed : $this->colorAccentLight);

            // Track
            $svg .= '<rect x="' . $plotX . '" y="' . $y . '" width="' . $plotW . '" height="' . $barHeight . '" fill="' . $this->colorTrack . '" rx="4"/>';

            // Bar
            $svg .= '<rect x="' . $plotX . '" y="' . $y . '" width="' . $barW . '" height="' . $barHeight . '" fill="' . $color . '" rx="4"/>';

            // Label (Oy) — truncate to 20 chars
            $label = mb_strlen($labels[$i]) > 20 ? mb_substr($labels[$i], 0, 20) . '…' : $labels[$i];
            $svg .= $this->text($label, $plotX - 6, $y + ($barHeight / 2) + 4, $this->colorMuted, 10, 'end');
        }

        $svg .= '</svg>';

        return $svg;
    }

    // ─────────────────────────────────────────────
    // SVG HELPERS
    // ─────────────────────────────────────────────

    private function openSvg(int $w, int $h): string
    {
        return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" xmlns="http://www.w3.org/2000/svg">';
    }

    private function hLine(float $x1, float $y, float $x2, string $color, string $dash = ''): string
    {
        $dashAttr = $dash ? ' stroke-dasharray="' . $dash . '"' : '';
        return '<line x1="' . $x1 . '" y1="' . $y . '" x2="' . $x2 . '" y2="' . $y . '" stroke="' . $color . '" stroke-width="1"' . $dashAttr . '/>';
    }

    private function vLine(float $x, float $y1, float $y2, string $color, string $dash = ''): string
    {
        $dashAttr = $dash ? ' stroke-dasharray="' . $dash . '"' : '';
        return '<line x1="' . $x . '" y1="' . $y1 . '" x2="' . $x . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="1"' . $dashAttr . '/>';
    }

    private function text(string $content, float $x, float $y, string $color, int $size, string $anchor = 'start'): string
    {
        return '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-size="' . $size . '" text-anchor="' . $anchor . '" font-family="DejaVu Sans, sans-serif">' . htmlspecialchars($content) . '</text>';
    }

    /**
     * Build a smooth cubic Bezier path string using Catmull-Rom conversion.
     */
    private function smoothPath(array $points): string
    {
        $n  = count($points);
        $d  = 'M' . $points[0]['x'] . ',' . $points[0]['y'];

        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $points[max(0, $i - 1)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($n - 1, $i + 2)];

            $cp1x = round($p1['x'] + ($p2['x'] - $p0['x']) / 6, 2);
            $cp1y = round($p1['y'] + ($p2['y'] - $p0['y']) / 6, 2);
            $cp2x = round($p2['x'] - ($p3['x'] - $p1['x']) / 6, 2);
            $cp2y = round($p2['y'] - ($p3['y'] - $p1['y']) / 6, 2);

            $d .= ' C' . $cp1x . ',' . $cp1y . ' ' . $cp2x . ',' . $cp2y . ' ' . $p2['x'] . ',' . $p2['y'];
        }

        return $d;
    }

    // ─────────────────────────────────────────────
    // MATH HELPERS
    // ─────────────────────────────────────────────

    private function niceMax(array $values): int
    {
        $max = max($values);
        $exp = (int) floor(log10($max));
        $pow = 10 ** $exp;

        return (int) (ceil($max / $pow) * $pow);
    }
}
