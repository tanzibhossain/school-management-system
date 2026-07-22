# 20 — Website

**Status:** ✅ Done · **Depends on:** — · **Path:** `app/Modules/Website`

## Scope
This module powers the public website experience for a school, including pages, menus, layouts, site settings, media, notices, routine viewing, stats, and public result checking.

## Tables
| Table | Purpose / key columns |
|---|---|
| `pages` | public page content |
| `page_redirects` | URL redirects |
| `page_layouts` | page-level layout definitions |
| `site_layouts` | shared site chrome/layout definitions |
| `site_settings` | site configuration values |
| `menus`, `menu_items` | navigation structure |
| `page_templates` | reusable page templates |
| `website_media` | uploaded media assets |

## API Endpoints
- Public routes under `/v2/public/*` for pages, site chrome, notices, staff, routine, stats, and result checks
- Admin management endpoints for pages, layouts, settings, menus, and media

## Block Style & Layout (page builder)
Every block in `page_layouts.layout_json` (`blocks[]`/`sidebar[]`) carries three
keys, not just `type`/`data`:

```
{ "type": "staff", "data": {...content fields...}, "style": {...}, "layout": {...} }
```

- **`style`** (Style tab, same fields for every block type — "essentials" tier,
  not a full Elementor clone): `padding_top`/`padding_bottom`/`margin_top`/
  `margin_bottom` (px), `bg_color`, `bg_image` + `bg_overlay` (0–100), `text_color`,
  `radius` (px), `shadow` (`sm`/`md`/`lg`), `animation` (`fade`/`up`). Sanitized/
  clamped in one place — `PageRenderService::sanitizeStyle()` — called from both
  the admin save path (`PageController::normalizeBlocks`) and the render path
  (`PageRenderService::cleanBlocks`), so stored JSON can never carry an
  out-of-range value or stray CSS.
- **`layout`**: `columns` (per-breakpoint grid width — only meaningful for the
  five grid-of-cards block types: `staff`, `notices`, `stats`, `gallery_photo`,
  `gallery_video`) and `hide` (per-breakpoint visibility, every block type).
  Sanitized by `PageRenderService::sanitizeLayout()`.
- Four editor breakpoints (mobile/tablet/laptop/desktop) map onto Bootstrap
  5's own scale (base/md/lg/xl). `App\Modules\Website\Support\BlockPresentation`
  turns `style`+`layout` into wrapper CSS classes/inline styles shared by both
  `public/blocks/render.blade.php` (main column) and `public/sidebar/render.blade.php`
  — visibility reuses Bootstrap's `d-*-none`/`d-*-block` utilities (chained
  across breakpoints) and columns reuse `row-cols-*`, deliberately avoiding a
  bespoke `<style>`/media-query generator.
- Admin editor: each block card in `resources/views/admin/website/pages/_card.blade.php`
  has Content/Style/Layout tabs; the latter two are universal partials
  (`_style_fields.blade.php`/`_layout_fields.blade.php`), not per-type — one
  system to learn regardless of block type.
- Entrance animations are intentionally minimal (fade/slide-up only, no
  library) — a `.reveal` class + a small IntersectionObserver in
  `public/layout.blade.php`, respecting `prefers-reduced-motion`.

## Services & Business Rules
- Each save creates a new versioned row for layout-related records.
- Public endpoints are designed for site consumption and do not require the school dashboard session.
- The module is intentionally content-driven and versioned.

## Integration Points
- Consumes school data and announcements.
- Supports public views for academic routine, notices, and school statistics.
