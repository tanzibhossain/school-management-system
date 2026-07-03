<?php

namespace App\Modules\IdCard\Services;

use App\Modules\IdCard\Models\IdCardTemplate;

/**
 * Pure HTML builder — no DB/queue access. Given a template's styling config
 * and a flat "card data" array (built by IdCardBatchService per student/staff
 * record), produces one <div class="card"> per layout, and wraps N of them
 * into a print-ready sheet (8 cards per A4, per DevPlan spec).
 *
 * Photo and name are always shown (core to any card); every other field is
 * gated by IdCardTemplate::visible_fields so a school can hide what it
 * doesn't want printed (e.g. blood_group, school_phone).
 *
 * Only horizontal_classic and vertical are implemented. The other 3 layout
 * enum values (horizontal_modern, dual_stripe, minimal) fall back to
 * horizontal_classic until they're built — the schema already supports them.
 */
class IdCardRenderer
{
    /** Card size in mm — standard CR80 ID card (85.60mm x 53.98mm). */
    private const CARD_WIDTH_MM = 85.6;

    private const CARD_HEIGHT_MM = 54.0;

    /**
     * @param  array<string, mixed>  $card  Flat field => value map, see IdCardBatchService::cardData().
     */
    public function render(IdCardTemplate $template, array $card): string
    {
        return match ($template->layout) {
            'vertical' => $this->renderVertical($template, $card),
            default => $this->renderHorizontalClassic($template, $card),
        };
    }

    /**
     * Wrap pre-rendered card divs into a print sheet — 2 columns x 4 rows per
     * A4 page (8 per page, per DevPlan), dashed crop-mark borders around each
     * cell so cards can be cut apart. Built as a <table> rather than CSS grid
     * or flexbox — dompdf's CSS support is table-layout-first and doesn't
     * reliably handle `display: grid`.
     *
     * @param  array<int, string>  $cardHtmls  One pre-rendered <div class="card"> per entry.
     */
    public function wrapSheet(array $cardHtmls, IdCardTemplate $template): string
    {
        $fontFamily = $this->fontFamily($template->font);
        $rows = '';

        foreach (array_chunk($cardHtmls, 2) as $pair) {
            $cells = array_map(
                fn (string $card) => "<td style=\"padding:2mm; border:1px dashed #bbb;\">{$card}</td>",
                $pair,
            );
            // Pad an odd trailing row to 2 columns so the table doesn't stretch the lone cell.
            if (count($cells) === 1) {
                $cells[] = '<td></td>';
            }
            $rows .= '<tr>'.implode('', $cells).'</tr>';
        }

        return <<<HTML
            <html>
            <head>
            <style>
                @page { size: A4; margin: 8mm; }
                body { font-family: {$fontFamily}; margin: 0; }
                table.sheet { border-collapse: collapse; width: 100%; }
                table.sheet td { width: {$this->mm(self::CARD_WIDTH_MM)}; }
                .card {
                    width: {$this->mm(self::CARD_WIDTH_MM)};
                    height: {$this->mm(self::CARD_HEIGHT_MM)};
                    overflow: hidden;
                    position: relative;
                }
            </style>
            </head>
            <body>
                <table class="sheet">{$rows}</table>
            </body>
            </html>
            HTML;
    }

    /** @param array<string, mixed> $card */
    private function renderHorizontalClassic(IdCardTemplate $template, array $card): string
    {
        $bg = e($template->background_color);
        $accent = e($template->accent_color);
        $font = $this->fontFamily($template->font);
        $photo = $this->photoTag($card['photo_url'] ?? null);
        $name = e($card['name']);
        $rows = $this->fieldRows($template, $card);
        $logo = $this->logoTag($card['logo_url'] ?? null);
        $schoolName = e($card['school_name_header']);

        return <<<HTML
            <div class="card" style="font-family:{$font}; background:{$bg}; display:flex; flex-direction:column;">
                <div style="background:{$accent}; color:#fff; padding:4px 6px; font-size:9px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{$logo}{$schoolName}</span>
                </div>
                <div style="display:flex; flex:1; padding:6px; gap:6px;">
                    <div>{$photo}</div>
                    <div style="font-size:8px; line-height:1.4;">
                        <div style="font-size:11px; font-weight:bold; margin-bottom:2px;">{$name}</div>
                        {$rows}
                    </div>
                </div>
            </div>
            HTML;
    }

    /** @param array<string, mixed> $card */
    private function renderVertical(IdCardTemplate $template, array $card): string
    {
        $bg = e($template->background_color);
        $accent = e($template->accent_color);
        $font = $this->fontFamily($template->font);
        $photo = $this->photoTag($card['photo_url'] ?? null, 40);
        $name = e($card['name']);
        $rows = $this->fieldRows($template, $card);
        $logo = $this->logoTag($card['logo_url'] ?? null);
        $schoolName = e($card['school_name_header']);

        return <<<HTML
            <div class="card" style="font-family:{$font}; background:{$bg}; display:flex; flex-direction:column; align-items:center; text-align:center;">
                <div style="background:{$accent}; color:#fff; width:100%; padding:3px; font-size:8px; box-sizing:border-box;">{$logo}{$schoolName}</div>
                <div style="margin-top:4px;">{$photo}</div>
                <div style="font-size:10px; font-weight:bold; margin-top:2px;">{$name}</div>
                <div style="font-size:7px; line-height:1.3; margin-top:2px;">{$rows}</div>
            </div>
            HTML;
    }

    /**
     * Build the "Class: 8A" / "Blood Group: O+" style lines the school opted
     * to show. $card['labels'] and $card['values'] are parallel field => ...
     * maps built by IdCardBatchService::cardData(); a field is only rendered
     * if the template's visible_fields lists it AND it has a non-empty value.
     */
    private function fieldRows(IdCardTemplate $template, array $card): string
    {
        $visible = $template->visible_fields ?? [];
        $labels = $card['labels'] ?? [];
        $values = $card['values'] ?? [];
        $rows = '';

        foreach ($visible as $field) {
            if (! empty($labels[$field]) && $values[$field] !== null && $values[$field] !== '') {
                $label = e($labels[$field]);
                $value = e((string) $values[$field]);
                $rows .= "<div>{$label}: {$value}</div>";
            }
        }

        return $rows;
    }

    private function photoTag(?string $url, int $sizePx = 44): string
    {
        if (! $url) {
            return "<div style=\"width:{$sizePx}px; height:{$sizePx}px; background:#eee; border:1px solid #ccc;\"></div>";
        }

        $url = e($url);

        return "<img src=\"{$url}\" style=\"width:{$sizePx}px; height:{$sizePx}px; object-fit:cover; border:1px solid #ccc;\" />";
    }

    private function logoTag(?string $url): string
    {
        if (! $url) {
            return '';
        }

        $url = e($url);

        return "<img src=\"{$url}\" style=\"height:14px; vertical-align:middle; margin-right:4px;\" />";
    }

    private function fontFamily(string $font): string
    {
        return match ($font) {
            'serif' => 'Georgia, serif',
            'mono' => 'monospace',
            default => 'Helvetica, Arial, sans-serif',
        };
    }

    private function mm(float $value): string
    {
        return $value . 'mm';
    }
}
