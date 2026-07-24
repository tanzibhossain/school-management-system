<?php

namespace App\Modules\Website\Support;

/**
 * Turns a page block's sanitized 'style'/'layout' arrays (see
 * PageRenderService::sanitizeStyle()/sanitizeLayout()) into the wrapper CSS
 * class/inline-style attributes the public block partials render with.
 *
 * Kept deliberately framework-light (plain arrays in, strings out) so both
 * public/blocks/render.blade.php (main column) and public/sidebar/render.blade.php
 * (sidebar widgets) can share the exact same presentation logic — one place
 * that knows how a "hide on tablet" toggle or a shadow preset becomes real
 * CSS, so the two renderers can never quietly drift apart.
 *
 * Responsive strategy: four editor breakpoints (mobile/tablet/laptop/desktop)
 * map onto Bootstrap 5's own scale (base/md/lg/xl) so visibility and column
 * count reuse Bootstrap's existing `d-*` / `row-cols-*` utilities instead of
 * generating bespoke <style> blocks with media queries.
 */
class BlockPresentation
{
    /** Editor breakpoint => Bootstrap infix, in mobile-first order. */
    private const BREAKPOINTS = [
        'mobile' => '',
        'tablet' => '-md',
        'laptop' => '-lg',
        'desktop' => '-xl',
    ];

    private const SHADOWS = [
        'sm' => '0 1px 3px rgba(15, 23, 42, .08)',
        'md' => '0 6px 16px rgba(15, 23, 42, .10)',
        'lg' => '0 16px 40px rgba(15, 23, 42, .14)',
    ];

    /**
     * @param  array<string, mixed>  $style
     * @param  array<string, mixed>  $layout
     * @return array{class: string, style: string}
     */
    public static function wrapper(array $style, array $layout): array
    {
        return [
            'class' => trim(implode(' ', array_filter([
                'block-wrap',
                self::visibilityClasses($layout['hide'] ?? []),
                self::animationClass($style['animation'] ?? null),
            ]))),
            'style' => self::inlineStyle($style),
        ];
    }

    /**
     * The `row-cols-*` chain for a grid-of-cards block (staff, notices,
     * stats, galleries), honouring any per-breakpoint override and falling
     * back to that block's sensible default where the editor left it blank.
     *
     * @param  array<string, mixed>  $layout
     * @param  array<string, int>  $defaults  e.g. ['mobile' => 2, 'tablet' => 3, 'laptop' => 4, 'desktop' => 4]
     */
    public static function columnClasses(array $layout, array $defaults): string
    {
        $columns = $layout['columns'] ?? [];
        $classes = [];

        foreach (self::BREAKPOINTS as $bp => $infix) {
            $n = max(1, min(6, (int) ($columns[$bp] ?? $defaults[$bp] ?? 1)));
            $classes[] = "row-cols{$infix}-{$n}";
        }

        return implode(' ', $classes);
    }

    /** @param  array<string, mixed>  $hide */
    private static function visibilityClasses(array $hide): string
    {
        $classes = [];
        foreach (self::BREAKPOINTS as $bp => $infix) {
            $classes[] = "d{$infix}-".(! empty($hide[$bp]) ? 'none' : 'block');
        }

        return implode(' ', $classes);
    }

    private static function animationClass(?string $animation): string
    {
        return $animation ? "reveal reveal-{$animation}" : '';
    }

    /** @param  array<string, mixed>  $style */
    private static function inlineStyle(array $style): string
    {
        $rules = [];

        if (! empty($style['padding_top'])) {
            $rules[] = 'padding-top:'.((int) $style['padding_top']).'px';
        }
        if (! empty($style['padding_bottom'])) {
            $rules[] = 'padding-bottom:'.((int) $style['padding_bottom']).'px';
        }
        if (! empty($style['padding_left'])) {
            $rules[] = 'padding-left:'.((int) $style['padding_left']).'px';
        }
        if (! empty($style['padding_right'])) {
            $rules[] = 'padding-right:'.((int) $style['padding_right']).'px';
        }
        if (! empty($style['margin_top'])) {
            $rules[] = 'margin-top:'.((int) $style['margin_top']).'px';
        }
        if (! empty($style['margin_bottom'])) {
            $rules[] = 'margin-bottom:'.((int) $style['margin_bottom']).'px';
        }
        if (! empty($style['margin_left'])) {
            $rules[] = 'margin-left:'.((int) $style['margin_left']).'px';
        }
        if (! empty($style['margin_right'])) {
            $rules[] = 'margin-right:'.((int) $style['margin_right']).'px';
        }

        // Width — 'default' (or unset) applies no override at all, matching
        // this block's behavior before this control existed.
        if (($style['width_mode'] ?? null) === 'full') {
            $rules[] = 'width:100%';
        } elseif (($style['width_mode'] ?? null) === 'inline') {
            $rules[] = 'display:inline-block';
            $rules[] = 'width:auto';
        } elseif (($style['width_mode'] ?? null) === 'custom' && ! empty($style['width_value'])) {
            $unit = in_array($style['width_unit'] ?? null, ['%', 'px', 'em', 'rem'], true) ? $style['width_unit'] : '%';
            $rules[] = 'width:'.self::trimNumber((float) $style['width_value']).$unit;
        }

        if (! empty($style['bg_image'])) {
            $overlay = max(0, min(100, (int) ($style['bg_overlay'] ?? 0))) / 100;
            $image = str_replace("'", "\\'", $style['bg_image']);
            $rules[] = "background-image:linear-gradient(rgba(0,0,0,{$overlay}),rgba(0,0,0,{$overlay})),url('{$image}')";
            $rules[] = 'background-size:cover';
            $rules[] = 'background-position:center';
        } elseif (! empty($style['bg_color'])) {
            $rules[] = 'background-color:'.$style['bg_color'];
        }

        if (! empty($style['text_color'])) {
            $rules[] = 'color:'.$style['text_color'];
        }

        // Radius — per-side (radius_top/bottom/left/right) wins whenever ANY
        // of the four is set; the legacy single 'radius' (pre-§7aa, applied
        // uniformly to all four corners) is only used as a fallback for a
        // page saved before this control existed and never re-edited since.
        $radiusSides = ['top', 'bottom', 'left', 'right'];
        $hasPerSideRadius = false;
        foreach ($radiusSides as $side) {
            if (! empty($style["radius_{$side}"])) {
                $hasPerSideRadius = true;
                break;
            }
        }
        if ($hasPerSideRadius) {
            foreach ($radiusSides as $side) {
                if (! empty($style["radius_{$side}"])) {
                    $rules[] = "border-{$side}-radius:".((int) $style["radius_{$side}"]).'px';
                }
            }
            $rules[] = 'overflow:hidden';
        } elseif (! empty($style['radius'])) {
            $rules[] = 'border-radius:'.((int) $style['radius']).'px';
            $rules[] = 'overflow:hidden';
        }

        // Border — width-per-side only actually draws a visible line once a
        // real border-style is set (a plain border-width with no style is
        // invisible per the CSS spec, browser default style is 'none'), so
        // everything here is gated on that.
        if (! empty($style['border_style']) && $style['border_style'] !== 'none') {
            $rules[] = 'border-style:'.$style['border_style'];
            foreach (['top', 'bottom', 'left', 'right'] as $side) {
                if (! empty($style["border_width_{$side}"])) {
                    $rules[] = "border-{$side}-width:".((int) $style["border_width_{$side}"]).'px';
                }
            }
            if (! empty($style['border_color'])) {
                $rules[] = 'border-color:'.$style['border_color'];
            }
        }

        if (! empty($style['shadow']) && isset(self::SHADOWS[$style['shadow']])) {
            $rules[] = 'box-shadow:'.self::SHADOWS[$style['shadow']];
        }

        return implode(';', $rules);
    }

    /** Formats a float without a trailing ".0" (e.g. "75" not "75.0", but "33.5" stays "33.5"). */
    private static function trimNumber(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
