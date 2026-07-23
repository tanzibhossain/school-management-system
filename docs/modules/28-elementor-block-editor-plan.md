# Website Page Builder — Elementor-style Live Editor · Plan

**Status:** 🔵 **Planned, not started** · **Path:** `app/Modules/Website`, `app/Http/Controllers/Admin/Website`,
`resources/views/admin/website/pages`, `resources/views/public` · **Depends on:** `20-website.md` §"Block
Style & Layout" (✅ shipped — the Style/Layout tabs, `PageRenderService::sanitizeStyle/sanitizeLayout`, and
`BlockPresentation` this plan builds on top of).

**Resume note:** this file is the single source of truth for this feature. A fresh session (or a fresh Claude
instance) should be able to read this file top to bottom and continue at whichever milestone has status
`planned`/`in progress`, without re-deriving anything from chat history. Update the milestone table and the
"Decisions" section as you go — that's what makes resuming after a context reset cheap.

---

## 1. Goal

Today the admin block editor is a flat stack of block cards, each with Content/Style/Layout tabs, plus a "View
Live" link that opens the real page in a new tab. The user has to save and reload to see the effect of a
change.

The goal is an editor that **looks and operates like Elementor (+ the Elementor Pro conveniences that make
sense for a single-school website builder)**: a live canvas showing the real page, updating as you edit, a
narrow settings panel instead of a stacked card list, click-to-select on the canvas, a responsive-viewport
toggle, and a few Pro-grade quality-of-life features (revision history, copy/paste style, undo).

## 2. Scope reality check — what "like Elementor" means here

Elementor (and Elementor Pro) is a mature, multi-year commercial product with a huge surface area (Theme
Builder, Popup Builder, a Forms widget system, dynamic tags, WooCommerce builder, a nested Flexbox-container
layout engine, global widgets, a template library/marketplace, motion effects, custom CSS per element,
white-label branding…). Cloning all of it is not a realistic goal for a single-school admin tool, and most of
it doesn't map to this app's needs. This plan targets the subset that actually matters: **the live-editing
experience and the polish that comes with it**, not every menu item Elementor has.

**In scope** (this plan): live iframe preview, rail-and-panel editor layout, click-to-select, responsive
viewport toggle, drag-reorder in the block rail, revision history (free — reuses the existing versioned-row
model), copy/paste block style, session undo/redo.

**Explicitly out of scope** (call out if the user wants one of these later — each is its own plan-sized
project): a nested Section→Column→Widget/Container layout model (see §7, flagged as a future, separate,
data-model-changing project), a Theme Builder (this app already has header/footer/site chrome via `SiteLayout`
+ `Menu` — no need to rebuild that inside the page editor), a Popup Builder, a generic drag-and-drop Forms
widget (the app already has purpose-built `admission_form`/`contact` blocks), Dynamic Tags, global/reusable
widgets library, a template marketplace.

**Hard constraint carried over from the rest of the app:** no build step. Everything here is vanilla JS +
Blade + CDN libraries (Bootstrap 5.3.3 already loaded; may add SortableJS from CDN for drag-reorder — no
bundler, no npm build, no React/Vue). This mirrors how Elementor itself actually works under the hood (PHP
re-renders a widget server-side on each edit and the browser injects the returned HTML into an iframe) — so
the "no build step" constraint is not actually a limitation for this feature, it's the same architecture.

## 3. Current state recap (read this before touching code)

Content model — `page_layouts.layout_json` (LONGTEXT, every save is a **new row**, never mutated):
```
{ "template": "full" | "sidebar", "blocks": [ {type,data,style,layout}, ... ], "sidebar": [ {type,data,style,layout}, ... ] }
```
Flat arrays — no nesting/columns. 12 main-column block types + 4 sidebar block types (16 total), listed in
`PageRenderService::BLOCKS` / `::SIDEBAR_BLOCKS`. Grid-of-cards types (`staff`, `notices`, `stats`,
`gallery_photo`, `gallery_video`) get column controls; all 16 get visibility controls.

Key files as they exist today:
- `app/Modules/Website/Services/PageRenderService.php` — `buildView()`/`normalize()`/`cleanBlocks()` build the
  render-ready view model from `layout_json`; `sanitizeStyle()`/`sanitizeLayout()` are the single sanitization
  boundary (called from both admin save and public render — **reuse these, don't duplicate**).
- `app/Modules/Website/Support/BlockPresentation.php` — turns sanitized `style`/`layout` into wrapper CSS
  class/inline-style strings; shared by both renderers below.
- `resources/views/public/blocks/render.blade.php`, `resources/views/public/sidebar/render.blade.php` — the
  actual per-block-type HTML. **This is the single source of truth for what a block looks like on the real
  site** — the live preview must render through these same views, not a reimplementation.
- `resources/views/public/templates/{full,sidebar}.blade.php` — page-level template wrappers.
- `resources/views/public/layout.blade.php` — site chrome (header/footer/CSS vars/reveal-animation JS).
- `app/Http/Controllers/Admin/Website/PageController.php` — `edit()`/`layoutForEditor()`/`normalizeBlocks()`
  (admin save path) and presumably the public `show()` action (verify method name before Phase 1).
- `resources/views/admin/website/pages/edit.blade.php` + `_card.blade.php` + `_fields.blade.php` +
  `_style_fields.blade.php` + `_layout_fields.blade.php` — today's stacked-card editor UI (Phase 3 replaces
  the layout but keeps these partials as the content of the settings panel).
- Routes (`routes/web.php` ~line 300, group prefix `admin`, name prefix presumably `admin.website.pages.`):
  `pages.index/create/store/edit/save/homepage/destroy`. Phase 1 adds `pages.preview`.

## 4. Architecture for live preview

1. **New preview endpoint** — `POST /admin/pages/{id}/preview` (and a variant for not-yet-created pages, e.g.
   `POST /admin/pages/preview` taking `template` in the body, for the "create" screen). Controller action reads
   the posted (unsaved) `blocks`/`sidebar`/`template` from the request — same shape the save action already
   parses — runs them through the **existing** `normalizeBlocks()` → `sanitizeStyle()`/`sanitizeLayout()` →
   `PageRenderService::buildView()`-equivalent pipeline, and renders the **real** `public.templates.*` +
   `public.layout` view chain, returning full HTML. No DB write, no cache (ephemeral, ignore the "no cache on
   writes" rule only because this isn't a write at all).
2. **`PageRenderService` needs one addition**: a method that builds the view model from an **in-memory** blocks
   array instead of always loading `page_layouts.layout_json` from the DB (e.g. `buildViewFromRaw(array
   $blocks, array $sidebar, string $template)`). Refactor `buildView()` to share this with the DB-loading path
   rather than duplicating logic — same pattern already used for `sanitizeStyle`/`sanitizeLayout`.
3. **Client side**: serialize the whole editor form (all blocks + style + layout + template) to JSON, debounce
   ~300–400ms after the last input event, `fetch()` POST to the preview endpoint, get HTML back, and set it
   into the canvas iframe via `iframe.srcdoc` (simplest; full reload per change — fine for Phase 2). Phase 6
   upgrades this to a per-block partial render + targeted DOM patch so it stops flashing/losing scroll
   position on every keystroke.
4. Because the preview renders through the exact same Blade views as the live site, **preview and reality can
   never drift** — this is the property that makes the whole feature trustworthy, keep it that way through
   every later phase (never let Phase 3+ UI work introduce a second rendering path for "the canvas").

## 5. Milestones

| # | Milestone | What ships | Status |
|---|---|---|---|
| 1 | **Preview render endpoint** | `PageRenderService::buildViewFromBlocks()` (shared by `buildView()`); `PageController::preview()`; route `admin.pages.preview` (`POST /admin/pages/{id}/preview`); returns full page HTML from posted (unsaved) block data through the real render pipeline. Scoped to existing pages only — the "create new page" screen doesn't have live preview yet (page must be saved once first) | ✅ done |
| 2 | **Iframe canvas, debounced full reload** | `edit.blade.php` restructured into a two-column row (editor left, sticky live-preview iframe right, `col-lg-6`/`col-lg-6`); vanilla JS (`schedulePreview()`) debounces 350ms on any form `input`/`change` (incl. TinyMCE `change input undo redo` via `editor.save()`), block add/remove/reorder, and template switch, POSTs the whole form (minus the spoofed `_method` field) to the preview endpoint, sets `iframe.srcdoc`. In-flight requests are aborted via `AbortController` if a newer edit supersedes them. Iframe is sandboxed (`allow-same-origin allow-scripts`, no `allow-forms`) so the embedded contact form can't actually submit during preview | ✅ done |
| 3 | **Rail + panel editor layout** | Implemented as a collapsible rail rather than a separate offcanvas/panel — reparenting a block's fields into a shared offcanvas element would have desynced its form-submission order from its visual row position (reorder-via-DOM-move, the existing up/down mechanism, depends on each block's fields staying physically inside its own row wrapper). Instead: `_card.blade.php` split into a compact always-visible `.block-row` (drag-handle icon, type icon, label, up/down/remove, chevron) plus a `.block-settings` body (unchanged Content/Style/Layout tabs) that starts `display:none` and opens one-at-a-time per list via `openBlockCard()`/`closeBlockList()`/`toggleBlockCard()` in `edit.blade.php`. Newly-added blocks auto-open. Block-type icons added (`$blockIcons` map) | ✅ done |
| 4 | **Click-to-select + hover outline** | `PageRenderService`-driven templates (`templates/full.blade.php`, `templates/sidebar.blade.php`) now pass `index`/`group` into `public.blocks.render`/`public.sidebar.render`, which emit `data-block-index`/`data-block-group` on the block wrapper (inert on the real public site — only styled/interactive when `body.is-editor-preview` is present). `public/layout.blade.php` gains an iframe-only bridge script (`window.self !== window.top` guard) that adds hover/selected outlines and `postMessage`s `{source:'page-preview', type:'select-block', group, index}` to the parent on click, intercepting the click (capture phase, `preventDefault`+`stopPropagation`) so links/forms in the preview don't actually navigate/submit. `edit.blade.php` listens for that message and calls `openBlockCard()` on the matching rail row by **position** (the preview's block order already matches the form's current DOM order, since both come from the same FormData → `normalizeBlocks()` → `buildViewFromBlocks()` chain) | ✅ done |
| 5 | **Responsive viewport toolbar** | Desktop/Laptop/Tablet/Mobile icon buttons in the preview card header (`#viewport-toolbar`); clicking sets a `vp-laptop`/`vp-tablet`/`vp-mobile` class on `#preview-viewport-wrap` (desktop = no class = 100% width) which resizes `#preview-frame` via CSS only — no re-render needed, instant. Tablet/mobile get a subtle frame (box-shadow outline, mobile also rounded corners) | ✅ done |
| 6 | **Per-block partial re-render (perf)** | `POST /admin/pages/{id}/preview-block` (`PageController::previewBlock()`) takes one block's fields (`block[type]`, `block[data][...]`, `block[style][...]`, `block[layout][...]`, `group`, `contained`), runs them through the same `normalizeBlocks()`/`resolveBlockData()` pipeline, and renders just `public.blocks.render`/`public.sidebar.render` for that one block. Client (`edit.blade.php`): editing a field *inside* a block's `.block-settings` routes to `scheduleBlockPreview(card)` instead of the full `schedulePreview()`; it locates the matching element in the iframe via `[data-block-group][data-block-index]` (position = index into the block's own list, same convention as Milestone 4), fetches just that block's HTML, and does a targeted `replaceWith()` — structural changes (add/remove/reorder/template swap) and TinyMCE edits still call the appropriate path based on where the edit occurred. Falls back to a full reload whenever the fast path can't be trusted (iframe not settled yet, target element not found, request fails) so the preview can never get stuck | ✅ done |
| 7 | **Rail drag-reorder** | SortableJS 1.15.2 (CDN, no build step) initialized on `#blocks-list`/`#sidebar-list`, `handle: '.js-drag-handle'` (the grip icon already on every rail row since Milestone 3). `onEnd` triggers a full `schedulePreview()` reload, same as the up/down buttons — reordering shifts every subsequent block's position, so the per-block fast path from Milestone 6 doesn't apply here. New blocks added later are automatically draggable with no re-init needed (Sortable reads children live). **Dragging directly on the canvas (true Elementor behavior) stays out of scope** — cross-iframe drag-and-drop is materially harder and lower value here; revisit only if requested | ✅ done |
| 8 | **Revision history ("Pro" niceties, cheap)** | `page_layouts` is already versioned (every save = new row, never mutated) — add a "History" view listing past versions (created_at/by) with a "Restore" action that copies an old row's JSON into a new current save. Near-zero backend cost since the data already exists | planned |
| 9 | **Copy/paste block style ("Pro" niceties)** | "Copy style" / "Paste style" buttons per block in the settings panel; client-side only — stash the current block's `style` object in a JS variable, apply its values onto another block's hidden Style-tab inputs on paste. No backend change | planned |
| 10 | **Session undo/redo ("Pro" niceties)** | Client-side history stack of serialized form snapshots (JSON), Ctrl+Z/Ctrl+Shift+Z restores a snapshot into the form + re-triggers a preview render. Scoped to the current editing session only (not persisted) | planned |

Ship order matters less within 8–10 than 1→7; those three are independent add-ons and can be reordered or
dropped without affecting the others.

## 6. Testing

- **Feature test** for the preview endpoint: POST a blocks payload (including out-of-range style values) and
  assert the response HTML reflects sanitized values (e.g. a `padding_top` of `9999` gets clamped to `400`,
  same assertion style as the existing sanitizer tests) — this is the one milestone with real backend logic to
  cover.
- Everything from Phase 2 onward is editor JS/UX — no PHPUnit coverage; verify manually per milestone (this
  matches how the Style/Layout tabs work itself was verified — no way to check visuals from this sandbox, the
  user drives manual QA in-browser).
- Re-run `tests/Feature/Admin/` website suite after Phase 1 to confirm the new `buildViewFromRaw()` refactor
  didn't change behavior for the existing DB-loading path (`buildView()` should become a thin wrapper calling
  the new shared method with data loaded from `layout_json`).

## 7. Future / explicitly deferred — nested Section → Column → Widget layout

Real Elementor's structural model is nested containers (Section > Column > Widget, or newer Flexbox
Containers), not a flat top-to-bottom block stack. This app's `blocks[]` array is flat by design and changing
that is a **separate, larger, data-model-changing project**:
- Would need a `columns: [{width, blocks: [...]}]` shape (or a recursive `container` block type) added to the
  schema, plus recursive rendering in `render.blade.php`.
- Backward compatibility: existing `layout_json` rows are flat — treat each existing top-level block as an
  implicit full-width single-column row, so old content keeps rendering unchanged; only new content could use
  multi-column rows. No migration of historical rows required.
- Not started, not scheduled. Revisit only if the user explicitly asks for true multi-column row layouts (e.g.
  "put the staff grid next to the stats block side-by-side") — until then the flat model plus the grid-column
  controls already shipped (Module 20's Style/Layout work) covers the common cases.

## 7a. Rich text editor: Quill, not TinyMCE

Turned up while wiring the live preview to rich-text fields: `edit.blade.php` had a dead `initRichTextEditors()`
function targeting TinyMCE, but **no TinyMCE script was ever loaded anywhere in the app** — it was a no-op from
day one. The block editor's actual rich-text editing has always been powered by **Quill 2.0.2** (open source,
BSD-3, loaded globally from CDN in `layouts/admin.blade.php` — no API key, no build step), via a per-field inline
init script in `_fields.blade.php`. That per-field script only ran once at page load, though, so a richtext block
added later via "Add block" got an inert, un-initialized Quill container. Fixed by removing the dead TinyMCE code
and per-field script entirely, replacing it with one shared, idempotent `initQuillEditors()` in `edit.blade.php`
(guarded by `data-quill-init`, called on page load and again from `addBlock()`), and wiring Quill's `text-change`
into the same `scheduleBlockPreview()`/`schedulePreview()` routing every other field already uses — previously
richtext edits never triggered a live-preview update at all, since Quill syncs its hidden input via `.value =`,
which doesn't fire native `input`/`change` events. Also extended the Quill treatment to `image_text`'s `html`
field (previously a plain textarea despite rendering as raw HTML on the public site, same as `richtext`).

## 8. Decisions to confirm when resuming (if not already answered above)

- Confirm the exact current route/controller method name for the public page `show()` action before Phase 1
  (referenced as "presumably" above — verify, don't assume).
- Confirm whether Phase 8's "Restore" action should require a confirmation step (recommended: yes, native
  Bootstrap confirm modal, consistent with the delete/deactivate pattern used everywhere else in this admin).
- Milestones 8–10 are independent "Pro flavor" add-ons — confirm before starting them that they're still
  wanted, or whether to stop at Milestone 7 (core Elementor-like editing) and ship.
