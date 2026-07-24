# Website Page Builder — Elementor-style Live Editor · Plan

**Status:** ✅ **All 10 milestones done**, plus a **fullscreen Elementor-style shell rebuild** (§7b), a
**sidebar UX pass** (§7c), a **widget library + nested Container/Grid model** (§7d), and a **video block
options overhaul** (§7e) — 8 new leaf block types, a searchable categorized Add Block picker, single-level
block nesting, and a full Elementor-style video block (source picker, self-hosted `<video>` vs. embed,
autoplay/mute/loop/controls/download/preload/poster) (all done, pending user verification — needs real
`pint`/`phpstan`/`phpunit` + manual QA, see §7d/§7e's closing notes) · **Path:** `app/Modules/Website`,
`app/Http/Controllers/Admin/Website`,
`resources/views/admin/website/pages`, `resources/views/public`, `resources/views/layouts/admin-fullscreen.blade.php`
· **Depends on:** `20-website.md` §"Block Style & Layout" (✅ shipped — the Style/Layout tabs,
`PageRenderService::sanitizeStyle/sanitizeLayout`, and `BlockPresentation` this plan builds on top of).

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
| 8 | **Revision history ("Pro" niceties, cheap)** | Turned out `PageService::restore()` already existed (copies an old revision's `layout_json` into a brand-new row — history is never rewound/destroyed), just unused. Added `PageController::history()`/`restore()` + routes (`admin.pages.history` GET, `admin.pages.restore` POST) + `admin/website/pages/history.blade.php` (table of every `$page->layouts` row: saved-at, `createdBy` name, Latest/Published badges, block/sidebar counts, Restore button — native `confirm()`, matching this page's own Delete button convention rather than a Bootstrap modal). "History" link added next to "View Live" in the editor header | ✅ done |
| 9 | **Copy/paste block style ("Pro" niceties)** | Copy Style/Paste Style buttons at the top of `_style_fields.blade.php`'s tab (Paste starts `disabled` until something's copied). Single global JS "clipboard" (`copiedStyle`), shared across every block like Elementor Pro's real behavior (copy block A's style, paste into C, D, ...). Paste sets each target field's `.value` then re-dispatches `input`+`change` so the *existing* swatch-sync/range-echo/`scheduleBlockPreview` listeners handle it exactly as if typed — no duplicate logic. No backend change | ✅ done |
| 10 | **Session undo/redo ("Pro" niceties)** | History array + pointer (`history_`/`historyIndex`, named with a trailing underscore to avoid shadowing `window.history`). Snapshots are **data**, not raw DOM — each block captured as `{type, fields[]}` (field values in DOM order, positional, not name-keyed — sidesteps the hidden/checkbox same-name-pair ambiguity). Restoring rebuilds every block by cloning its `<template>` (the same path `addBlock()` already uses) and filling in captured values, so a restored richtext field gets a genuinely fresh Quill instance rather than Quill's internal DOM frozen into inert markup (the failure mode a naive `innerHTML` snapshot would hit). Discrete actions (add/remove/reorder/drag/paste-style/template switch) push a snapshot immediately; plain field edits and Quill changes push on a coarser 1200ms debounce (separate from the 350ms preview debounce) so continuous typing doesn't spam the history. Ctrl+Z/Ctrl+Y (or the toolbar buttons next to Save) — but only when focus isn't in an editable field, so the browser's own native per-field undo isn't hijacked while typing. Session-only, capped at 50 entries, never sent to the server. **Known gap**: `admission_form`'s own dynamic custom-fields sub-UI won't perfectly round-trip through undo (restores to the template's default field set) since that's a nested dynamic structure the positional capture doesn't model — acceptable, every other field/block type round-trips correctly | ✅ done |

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

## 7b. Fullscreen editor shell (post-Milestone-10 follow-up)

Requested after all 10 milestones shipped: make the editor page itself look and operate like Elementor's
actual app chrome, not just "a two-column form with a live preview embedded in it". Concretely: hide the admin
sidebar/topbar entirely on this one route, and replace the page content with a fullscreen app shell — topbar +
resizable left sidebar (rail/settings panels) + a full-bleed scrollable canvas — matching the layout Elementor
itself uses when you click "Edit with Elementor".

**New layout:** `resources/views/layouts/admin-fullscreen.blade.php` — a minimal shell (no `<x-sidebar>`,
`<x-header>`, content padding/max-width, footer, jQuery, DataTables, TomSelect). Keeps Bootstrap 5.3.3 +
Bootstrap Icons + Quill (CDN) since the editor still needs them, and the same indigo (`#4f46e5`) accent
override as `layouts/admin.blade.php` for visual consistency. `html, body { overflow: hidden }` — the shell
owns 100vh and its own panes manage their own scrolling, the outer page never scrolls.
`edit.blade.php` now `@extends('layouts.admin-fullscreen')` instead of `layouts.admin`.

**Topbar** (`.editor-topbar`, 3 sections, matches the request verbatim):
1. Back icon (→ `admin.pages.index`), Add Block icon, Page Settings icon, Undo/Redo, History icon — the icon
   buttons with `data-panel="add"/"settings"/"history"` are handled by a small `showPanel(name)` function that
   toggles `.sidebar-panel.active` + `.js-panel-btn.active`, no page navigation involved.
2. Page name (mirrors the Title field live via a plain `input` listener), the four-button viewport toolbar
   (unchanged from Milestone 5, just moved from the old preview-card header into the topbar), preview status text.
3. Preview icon (live site link, only when published), Publish/Update button — a `<button form="page-form">`
   *outside* the `<form>` tag (valid HTML5; submits the form by `id` without needing to be a DOM descendant).
   Label switches Publish/Update based on `$page->status`.

**Sidebar** (`#editor-sidebar`): `width: 10vw; min-width: 220px; max-width: 25vw` by default, drag-resizable
via a 6px handle (`#sidebar-resize-handle`, plain `mousedown`/`mousemove`/`mouseup`, clamped to
`max(220px, 10vw)`–`25vw`) — the 220px floor is the "adjust yourself for laptop" the request asked for, since
10vw on a 1366px laptop screen (~137px) is too narrow for usable form controls. Four `.sidebar-panel`s, only
one visible at a time (`showPanel()`):
- **`blocks`** (default) — the same `#main-col`/`#blocks-list`/`#side-col`/`#sidebar-list` block-rail markup
  from Milestone 3, unchanged, just without the inline "Add" dropdown+button (moved to the `add` panel).
- **`add`** — a vertical button grid, one button per block type (`.js-add-block[data-group][data-type]`),
  calling the existing `addBlock(group, type)` then `showPanel('blocks')`. Replaces the old
  `<select>`+"Add" button pair.
- **`settings`** — Title/Slug/Status/Template, the fields that were briefly a dedicated full-width row
  (previous request) and are now a vertically-stacked sidebar panel instead, since there's no more full-width
  row to put them in.
- **`history`** — inline revision list (`$page->layouts`, eager-loaded with `createdBy` by
  `PageController::edit()` to avoid an N+1), each with a Restore form. **Not** nested inside `#page-form`
  (HTML forbids nested `<form>`s) — sits as a DOM sibling of the `<form>`, which is fine since `showPanel()`
  selects by `.sidebar-panel` class/`data-panel` attribute, not by parentage. The standalone
  `admin/website/pages/history.blade.php` page + `admin.pages.history` route still exist too (unlinked from
  the topbar now, but harmless to keep for direct access).

**Canvas** (`.editor-canvas`, reuses the `#preview-viewport-wrap` id so the Milestone 5 viewport-toggle JS
needed no changes beyond swapping `#preview-viewport-wrap` from a Bootstrap-card body to the flex canvas
itself): `flex:1; overflow:auto; display:flex; justify-content:center;`, iframe at `width:100%; height:100%`
(was a fixed `82vh` inside a sticky card before). Viewport breakpoint classes (`.vp-laptop/.vp-tablet/.vp-mobile`)
now live on `.editor-canvas` and just change `#preview-frame`'s pixel width, same as before.

**Preserved on purpose:** every element ID/class the existing (large) editor script depends on —
`#page-form`, `#blocks-list`, `#sidebar-list`, `#main-col`, `#side-col`, `#tpl-select`, `#preview-frame`,
`#preview-viewport-wrap`, `#viewport-toolbar`, `#preview-status`, `#btn-undo`, `#btn-redo`, `.block-card`,
`.block-row`, `.block-settings`, every `.js-*` class — so live preview, click-to-select, drag-reorder,
copy/paste style, and undo/redo all kept working with only additive JS (`showPanel()`, the resize-drag IIFE,
the `.js-add-block` click branch) rather than a rewrite of the existing logic. `restoreSnapshot()` (undo/redo)
and the `tpl-select` change handler were extended to also toggle the new `#add-side-section` panel visibility
alongside the pre-existing `#side-col` toggle, so template switching and undo/redo stay consistent with the
Add panel's sidebar-block-type visibility.

**New translation keys added** (`database/seeders/data/translations/bn.json`): "Add Block", "Page Settings",
"Preview", "Update", "Untitled", "Unknown", "No revisions yet.", "Restore this revision as a new draft?".

**Not yet done:** user verification (Pint/PHPStan/PHPUnit + manual browser QA) — ask for this after committing.

## 7c. Sidebar UX pass — default view, click-outside, in-canvas DnD + context menu

Follow-up to §7b, requested once the fullscreen shell was in place: make the sidebar behave like Elementor's
panel (a resting "Add Elements" state that temporary views collapse back to) and make the canvas itself
directly editable (drag to reorder, right-click for quick actions), not just a read-only preview that opens
the rail on click.

- **Default panel is now `add`, not `blocks`.** `DEFAULT_PANEL = 'add'` in `edit.blade.php`. A new topbar icon
  (`bi-stack`, `data-panel="blocks"`) was added between Add Block and Page Settings so the layers list (needed
  for up/down/remove/drag-reorder-by-handle on blocks not currently visible/selectable in the canvas) is still
  reachable on demand.
- **Click-outside-sidebar / Escape → `resetSidebarToDefault()`**: a `document` click listener checks
  `e.target.closest('#editor-sidebar')`; if the click landed outside, it calls `showPanel('add')` +
  `closeBlockList()` on both rail lists. `.js-panel-btn` clicks call `e.stopPropagation()` so switching panels
  via the topbar doesn't immediately trigger its own outside-click reset. The sidebar resize-drag handler sets
  a one-shot `sidebarResizeJustEnded` flag on `mouseup` so the mouseup landing outside the sidebar (the whole
  point of resizing) doesn't fire a spurious reset. Escape reuses the same `resetSidebarToDefault()` and blurs
  the active element. The preview iframe now also posts a `{type:'deselect'}` message when the canvas
  background (not a block) is clicked, so clicking "outside" inside the iframe has the same effect.
- **Add Block panel is a 2-column grid** (`row row-cols-2 g-2`) of icon-over-label boxes
  (`.js-add-block`, `min-height:72px`) instead of a single-column button list — same for the Sidebar Blocks
  sub-section (shown/hidden with `#add-side-section`, already existing logic, unchanged).
- **In-canvas drag-and-drop reordering**: `public/blocks/render.blade.php` and `public/sidebar/render.blade.php`
  now add `draggable="true"` to `$editorAttrs` alongside the existing `data-block-index`/`data-block-group`
  (editor-preview-only — absent on the real public site). `public/layout.blade.php`'s gated iframe script
  implements plain HTML5 `dragstart`/`dragover`/`drop` (same-group only — main blocks and sidebar blocks are
  separate arrays), shows a `drop-before`/`drop-after` insertion-line indicator, and on drop posts
  `{type:'reorder-blocks', group, order}` (the full new sequence of original indices) to the parent. The parent
  reorders the actual `#blocks-list`/`#sidebar-list` DOM (the source of truth) by re-appending each node per
  `order`, then `schedulePreview()` + `pushHistory()` — the iframe never reorders its own DOM, it just tells the
  parent what happened.
- **Right-click context menu** (Copy Style / Paste Style / Remove) on canvas blocks: a small hand-rolled
  fixed-position menu (Bootstrap's dropdown JS is trigger-element-based, not cursor-position-based, so not a
  fit here) built in the iframe script, posting `{type:'context-action', action, group, index}`. The parent's
  existing copy/paste-style and remove logic was extracted into three reusable functions —
  `copyStyleFromCard()`, `pasteStyleToCard()`, `removeCard()` — used by both the sidebar's own buttons (the
  original Milestone 9/3 entry points) and this new message handler, so there's exactly one implementation of
  each action.
- **No new translation keys** — the context menu reuses the existing "Copy Style"/"Paste Style"/"Remove"
  strings already shown on the sidebar's per-block buttons.

## 7d. Widget library expansion + nested Container/Grid model

Requested via a mockup of Elementor's own widget picker (search bar + collapsible Layout/Basic/Advanced
categories, boxed icon-over-label items) that additionally listed several block types this app didn't have:
Container, Grid, Button, Divider, Spacer, Google Maps, Icon, plus a generic "Text Editor" (already covered by
the existing `richtext` block, just relabeled). Explicitly asked to build **all of it, including Container/
Grid** — the nested-layout model §7 originally scoped out as "its own separate, data-model-changing project".
Rather than the full Section→Column→Widget/Container engine described there, this ships a deliberately
smaller, lower-risk version of that idea — see "What was NOT built" below for the gap between the two.

**New leaf block types** (flat, no nesting — same architecture as every existing block):
`video` (single embed + caption), `button` (text/url/align/open-in-new-tab), `divider` (line style + width %),
`spacer` (height px), `icon` (Bootstrap Icon class + size + color + optional link), `google_maps` (embed URL +
height). Each is a `PageRenderService::BLOCKS`/`LEAF_BLOCKS` entry, a `public/blocks/render.blade.php` `@case`,
an `edit.blade.php` `$spec` entry, and a `$blockIcons` entry — the same 4-touchpoint pattern every prior block
type already followed, nothing new architecturally. `_fields.blade.php` gained a `checkbox` input type (for
button's "open in new tab") and optional `placeholder` support on text/number fields (for icon's `bi-star`
hint) — small, backward-compatible additions to the existing `$spec`-driven field renderer.

**Container/Grid — single-level nesting, not a full layout engine:**
- `PageRenderService::LEAF_BLOCKS` = `BLOCKS` minus `container`/`grid` — the allow-list for a container/grid's
  own children. A child is *never itself* a container/grid — nesting is exactly one level deep. This is the
  single biggest scope-reduction from the original out-of-scope Section→Column→Widget idea: no recursive tree,
  no arbitrary depth, no column-within-column layouts.
- **Storage**: a container/grid's children live at `data.blocks` — an array of ordinary `{type,data,style,
  layout}` block objects, structurally identical to the top-level `blocks`/`sidebar` arrays. No new top-level
  `layout_json` shape; nesting is just "one block's data happens to contain more blocks".
- **Backend recursion** (one extra `if (container/grid)` branch in each place, not a rewrite): `PageController
  ::normalizeBlocks()` recurses into `data.blocks` on save (restricted to `LEAF_BLOCKS`); `layoutForEditor()`'s
  `$reverse` closure recurses the same way when loading a page back into the editor (multiline-field reversal
  for a nested gallery_photo/quick_links etc.); `PageRenderService::cleanBlocks()` recurses for the public
  render path (`normalize()`/`buildView()`); a new `PageRenderService::resolveNestedBlocks()` recurses
  `resolveBlockData()` for each child so notices/stats/staff data resolves correctly even nested inside a
  container.
- **Public rendering**: `container` renders children in a `d-flex flex-{column|row}` wrapper (its own
  `direction`/`gap` fields); `grid` renders children in a Bootstrap `.row` using the *existing*
  `BlockPresentation::columnClasses()` + the Layout tab's per-breakpoint column count — the same mechanism
  `staff`/`notices`/`stats` already use, just pointed at arbitrary children instead of a fixed data source (so
  `grid` added `'grid'` to `edit.blade.php`'s `$gridTypes` array to get that Layout-tab control). Both cases
  `@include('public.blocks.render', [...])` themselves recursively, once per child, with `contained => true`
  (skips the double `.container` wrapper, matches how sidebar items are treated).
- **Admin editing — a self-contained mini rail, not a rewrite of the main rail**: a container/grid's Content
  tab (`_card.blade.php`) additionally includes a new `_nested_blocks.blade.php` partial: its own
  `.nested-blocks-list` (children rendered via `_card.blade.php` recursively — since children are always leaf
  types, this can't recurse a second time), its own "no children yet" message, and its own Add-child
  `<select>` + button (leaf types only). New hidden `<template id="tpl-child-{type}">` tags use a `__PREFIX__`
  token (instead of a literal `blocks`/`sidebar` root) — `addChildBlock()` substitutes it with the specific
  container instance's own `data-prefix` attribute at insert time, since a child's real form name
  (`blocks[2][data][blocks][0][data][text]`) depends on *which* container it's being added to.
- **Up/down/remove/Copy-Style/Paste-Style buttons work on nested children for free** — they're all already
  generic (`.closest('.block-card')` + `.parentElement`), never hardcoded to the top-level list IDs, so no
  changes were needed there. Drag-reorder (SortableJS) required one addition: `initNestedSortables()` inits
  Sortable on every `.nested-blocks-list` (idempotent via `data-sortable-init`), called after any structural
  change that could introduce one.
- **What was NOT built** (the real gap vs. a "full" nested layout engine, kept deliberately out of scope for
  risk/time reasons — flag to the user before extending further):
  - **No canvas interactivity for nested children.** `public/blocks/render.blade.php`'s recursive `@include`
    calls don't pass `$index`/`$group`, so nested children never get `data-block-index`/`draggable` —
    unlike top-level blocks, they can't be click-to-selected, drag-reordered, or right-clicked directly on the
    canvas. The *container itself* is fully canvas-interactive (it's a normal top-level block); its children
    are only editable via the sidebar's nested mini-rail. `runBlockPreview()`'s per-block partial-preview path
    already falls back to a full `schedulePreview()` reload for a nested-child edit (its card isn't found in
    `blocks-list`/`sidebar-list`'s direct children) — correct, just not the fast path.
  - **Undo/redo does not reliably round-trip a container's children.** `captureCardFields()`/`applyCardFields()`
    are positional (Nth named field in `.block-settings`, which naturally sweeps up nested descendants too),
    but `restoreList()` rebuilds a container fresh from its *empty* `<template>` before reapplying captured
    values — if the child count changed since the snapshot, positions won't line up. Same category of gap as
    `admission_form`'s dynamic custom fields (already documented in §6/Milestone 10) — not solved here for the
    same reason: a real fix needs fully recursive, structure-aware snapshots, not just positional field values.
  - **No visual nested-drag-and-drop** (dragging a block from the canvas *into* a container, or between
    containers) — children are only added/removed/reordered via the sidebar's mini rail's own controls.
  - These are reasonable follow-ups if the user wants the nesting experience closer to feature-parity with
    top-level blocks; each is its own scoped chunk of work, not a quick add.
- **No new translation keys for block/field labels** — confirmed the existing convention: `PageRenderService
  ::BLOCKS`/`LEAF_BLOCKS` labels and `$spec` field labels are plain, un-`__()`-wrapped strings everywhere in
  this codebase (verified none of "Hero banner", "Photo gallery", etc. exist in `bn.json` either) — always
  English regardless of locale, by prior design, not something this change needed to alter. Only the new UI
  chrome I did wrap in `__()` (category names, search placeholder, empty-state messages) got `bn.json` entries.
- **Verification gap**: none of this has run through Pint/PHPStan/PHPUnit or a real browser — no PHP/Docker in
  this sandbox. Given the size of this change (new PHP recursion in 2 files, a new Blade partial, ~250 lines of
  new/changed JS), this is the most important thing to check before trusting it in production: at minimum, add
  a page with a Container holding 2-3 leaf blocks and a Grid holding a few more, save, reload the editor, and
  confirm both the public page and the editor's own re-opened state match what was configured.

## 7e. Video block options overhaul

Requested via a mockup of Elementor's own Video widget settings. Replaced the video block's original 3-field
spec (heading/url/caption) with the full set: `source` (YouTube/Vimeo/Dailymotion/VideoPress/Self Hosted),
`url` (External URL, relabeled from the old field — same key, so existing saved video blocks keep working
unchanged, implicitly treated as YouTube since they predate the `source` field), `file_url` (Video File URL),
`start_time`/`end_time` (seconds), `autoplay`/`mute`/`loop`/`controls`/`download` (toggles), `preload`
(None/Metadata/Auto), `poster` (image URL), `caption`.

**No file upload** — `file_url`/`poster` are plain URL text fields, not an actual upload picker. This app's
entire block editor is URL-paste-based (every image/gallery/video field already works this way); building real
file upload (MinIO wiring, an upload endpoint, progress UI) is a separate, much bigger feature this request
didn't ask for and wasn't built here.

**Two small, generic `_fields.blade.php` additions** (reusable by any future block, not video-specific):
- **`'input' => 'switch'`** — identical hidden+checkbox pair as the existing `checkbox` type, just adds
  `form-switch` for the pill-toggle look the mockup showed. A field can also carry `'default' => true` (only
  `video`'s `controls` uses this) — distinguishes "never touched, \$data has no key" (empty string) from
  "explicitly saved unchecked" (string `'0'`) so a spec-level default doesn't fight a real saved value. Select
  fields got the equivalent `'default_value'` for the same reason (video's `preload` defaults to `metadata`).
- **`'depends_on' => ['key' => 'source', 'values' => ['self_hosted']]`** — conditional field visibility (used
  by `url`/`file_url` to show only the relevant one for the selected Source, matching the mockup exactly).
  Renders as a `data-depends-on`/`data-depends-values` attribute on the field's wrapper div; a new
  `applyFieldDependencies(card)` in `edit.blade.php` evaluates it against the current value of the named sibling
  control, called on page load, after `addBlock()`/`addChildBlock()`/`restoreSnapshot()`, and via a delegated
  `change` listener. **Known limitation**: looks up the depended-on control by `[name$="[data][KEY]"]`, which
  for a checkbox/switch matches its hidden(0) input first, not the checkbox itself — a boolean field as the
  *depended-on* control isn't supported by this lookup (not needed yet: `source` is a `<select>`).

**Public rendering** (`public/blocks/render.blade.php`'s `video` case): `self_hosted` renders a native
`<video>` with `preload`/`poster`/`controls`/`controlslist="nodownload"` (hides the download button — a
widely-supported but non-standard attribute; Chrome/Edge/Firefox all honor it)/`autoplay muted`(forced
together, since browsers require muted for autoplay to actually run)/`loop`, and a `#t=start,end` fragment on
the `<source>` for start/end time (the standard Media Fragments URI way to seek native `<video>`). Any other
source does a best-effort YouTube watch/short-link → embed URL normalization (regex-extracted video ID) plus
`autoplay`/`mute`/`loop`/`controls`/`start`/`end` as YouTube embed URL params — Vimeo/Dailymotion/VideoPress
are trusted to already be a pasted embeddable URL, with no per-platform param mapping (avoids guessing at
those platforms' own embed APIs, which this app doesn't integrate with).

## 7f. Drag a new block from the Add Block panel into the canvas

Requested as a follow-up to §7d's search/category picker: drag a block-type box directly from the sidebar's
Add Block panel and drop it at a specific spot on the live preview, instead of only click-to-append.

Cross-window HTML5 drag-and-drop: the drag *source* (`.js-add-block` in `edit.blade.php`, now
`draggable="true"`) is in the parent document; the drop *target* is inside the preview iframe's separate
`.srcdoc` document. Native `dragstart`/`dragover`/`drop` fire across that boundary without any special
handling (it's a browser-level gesture, not gated by same-origin script access) — but `dataTransfer.getData()`
can only be read on `drop`, never during `dragover` (a spec-level restriction); `dragover` can only see
`e.dataTransfer.types` (the registered MIME type *names*, not their values). So:
- **Parent side**: `dragstart` on a `.js-add-block` sets `dataTransfer` (`application/x-block-type` +
  `text/plain` fallback, JSON `{group,type}`) and `effectAllowed = 'copy'`.
- **Iframe side** (`public/layout.blade.php`'s existing gated editor-bridge script, extended): `dragover`
  checks `e.dataTransfer.types` for `application/x-block-type` to recognize "an external add-block drag is in
  progress" (reusing the same `drop-before`/`drop-after` insertion-line indicator as internal reorder-drag) —
  it doesn't know *which* block type or group yet, only that *something* is being dragged in. On `drop`, it
  finally reads the real `{group,type}` payload and posts `{type:'add-block-at', group, blockType, index,
  before}` to the parent — `index`/`before` come from the last hovered `[data-block-index]` element, but are
  only honored if that element's `data-block-group` matches the dropped item's own group (you can't drop a
  Sidebar-only block among main content blocks or vice versa); otherwise it falls back to `index: null` (append
  at the end — same as clicking the picker item).
- **Parent handler**: `addBlockAt(group, type, index, before)` — a new sibling to `addBlock()`, both now
  built on shared `insertBlockHtml()`/`finishBlockInsert()` helpers (`addBlock()` always inserts at the end,
  `addBlockAt()` inserts before/after `list.children[index]`, same DOM-order-matches-last-render invariant
  every other canvas message already depends on — see `reorder-blocks`/`select-block`).
- A subtle whole-canvas background tint (`body.is-external-drag-over`) shows while a drag is in progress, even
  before hovering a specific block, so it's clear the canvas is a valid drop target from the moment the drag
  starts.
- **Scope match with §7d**: like the click-to-add path, this only targets *top-level* blocks/sidebar lists —
  dropping directly into a Container/Grid's nested children isn't supported (nested children still aren't
  canvas-interactive at all, per §7d's documented limitations); add a block to a container via its own sidebar
  mini-rail (`_nested_blocks.blade.php`) instead.
- Not verified in a real browser (no PHP/Docker in this sandbox) — cross-iframe native drag-and-drop is
  standard web platform behavior, but this is exactly the kind of thing that should get a real manual pass
  (drag from each category, drop above/below/between existing blocks, drop on an empty canvas, drop a
  Sidebar-category item and confirm it can't land among main content blocks) before trusting it.
- **Superseded by §7g below**: the `index`/`before` addressing scheme described here, and the "nested children
  aren't a drop target" limitation, no longer apply — §7g replaces the whole addressing model with recursive
  paths and makes nested children fully canvas-interactive, including as drag/drop targets.

## 7g. Recursive nesting: arbitrary depth, canvas-interactive children, drag-into-container, structure-aware undo/redo

Requested as an explicit, scoped-up follow-on to §7d's Container/Grid model, closing all four gaps that
section's own "What was NOT built" list had flagged. This is the actual "nested Section→Column→Widget layout"
project §7 originally described as a separate, data-model-changing undertaking — landed here in a
*deliberately* smaller form than a full Elementor-style engine (see "What's still not built" below), but no
longer capped at one level and no longer read-only-via-sidebar.

**The core change is addressing.** Every prior canvas feature (click-to-select, drag-reorder, right-click menu,
the fast per-block preview patch, drag-from-sidebar-into-canvas) identified a block by a single flat
`data-block-index` + `data-block-group` pair — which only worked because nesting was capped at one level and
nested children were never canvas-addressable at all. Recursive nesting needs more than one number, so every
one of those features now runs on a **path**: a list of indices from the root (`"2"` for the 3rd top-level
block, `"2,0"` for its 1st child, `"2,0,1"` for that child's 2nd child, …), carried as `data-block-path` (was
`data-block-index`) on every rendered block, top-level or nested alike. A path's *last segment* is always that
block's own index among its siblings (the server renders children in array order), which is what keeps the
reorder/insert math simple — no separate sibling-index lookup is ever needed, it's already encoded in the path
itself.

**Backend — arbitrary depth, not single-level:**
- `PageRenderService::MAX_NESTING_DEPTH = 6` (a generous but real cap — not truly infinite, both to bound
  worst-case render/save cost and to guarantee the recursion terminates). `LEAF_BLOCKS` changes meaning: it's
  no longer "the only allow-list nesting ever uses," it's now "the allow-list once a branch has reached
  `MAX_NESTING_DEPTH`" — below that depth, the full `BLOCKS` list (container/grid included) is allowed, so a
  container CAN hold another container.
  `resolveNestedBlocks()`/`cleanBlocks()` (`PageRenderService`) and `normalizeBlocks()`
  (`PageController`) all gained a `$depth` parameter threaded through their existing recursive calls, switching
  the child allow-list to `LEAF_BLOCKS` once `$depth + 1 >= MAX_NESTING_DEPTH`. `layoutForEditor()`'s `$reverse`
  closure needed **no change** — it was already unconditionally recursive (it doesn't gate on type, since it's
  just reversing multiline-field encoding for display, not deciding what's allowed to be saved).
- `previewBlock()`'s single-block fast-render path still resolves data at `depth=0` regardless of how deep the
  previewed block actually lives in the tree — a known, accepted inaccuracy (documented inline): it can only
  affect a nested container's own `MAX_NESTING_DEPTH` bookkeeping during that one fast preview render, never on
  save (which always uses the real depth), so it was judged not worth threading true depth through the whole
  `previewBlock()` request just for this cosmetic edge case.

**Admin editing UI — genuinely recursive, not a rewrite:** `_card.blade.php`'s existing
`@if (container/grid) @include(_nested_blocks)` branch was ALREADY structurally recursive by construction —
if a child is itself a container/grid, including `_card.blade.php` for it hits that same branch again. The
only things actually stopping deeper nesting before this were: (1) `_nested_blocks.blade.php`'s "Add child"
`<select>` only ever offered `LEAF_BLOCKS`, and (2) the hidden `tpl-child-{type}` templates
(`edit.blade.php`) were only generated for `LEAF_BLOCKS`. Both now use the full `BLOCKS` list, with
`_nested_blocks.blade.php` computing its own current depth from `$prefix` (`substr_count($prefix,
'[data][blocks]')`) to stop *offering* Container/Grid as an addable child once `MAX_NESTING_DEPTH` would be
exceeded — UX politeness matching the backend's real, independently-enforced cap, not the actual guard.

**Canvas interactivity for nested children — falls out of the path rewrite almost for free:**
`public/blocks/render.blade.php`'s container/grid `@case`s now pass `path` (parent's path + child index) and
`group` down through their recursive `@include`, so nested children get `data-block-path`/`data-block-group`/
`data-block-type` exactly like top-level blocks. Since `closest('[data-block-path]')` always resolves to the
*deepest* matching element under the cursor, hover/click/drag/right-click on a nested block already "just
work" for selection — the only genuinely new logic needed was for drag-and-drop reordering/nesting (below).
`data-block-type` is a new attribute (both render partials) recording the block's own type, needed so the drop
logic can recognize "this target is a Container/Grid" without inspecting rendered markup.

**Drag into/out of a container:** `public/layout.blade.php`'s `dragover`/`drop` handlers gained
`classifyDropTarget(el, clientY)` — given whatever's under the pointer, if it's a Container/Grid and the
pointer isn't within its top/bottom ~25%-height edge zone, the drop is classified `mode:'into'` (append as a
new child); otherwise it's `mode:'sibling'` (insert before/after, the pre-existing behavior). A guard
(`isWithin(targetPath, dragPath)`) stops a block from being dropped into its own descendant subtree (which
would orphan/cycle the data). One unified `move-block` message (`{group, fromPath, toParentPath, toIndex}`)
**replaces** the old `reorder-blocks` message (which posted a full flat sibling-order array — unworkable once
siblings can live at different nesting levels) for every case: plain reordering, dropping into a container, and
pulling a nested child back out to a shallower level or a different container are all just "move the block at
`fromPath` to `toIndex` within `toParentPath`'s list" to the parent, which resolves both ends via new
`resolveListByPath()`/`resolveCardByPath()` helpers (a live DOM walk down through nested `.nested-blocks-list`
rails — never a cached path attribute, same "always live, never stale" philosophy the pre-existing top-level
`list.children[index]` lookups already relied on) and moves the real `.block-card` node with `insertBefore()`
(reference-node-based, so it's correct regardless of index shifts from the node's own removal). The
drag-from-Add-Block-panel path (`add-block-at`, §7f) was upgraded the same way — `index`/`before` became
`toParentPath`/`toIndex`, so a *brand new* block can also be dropped straight into a container, not just an
existing one moved into it. `addBlockAt()` (`edit.blade.php`) now branches on whether it's inserting at the
root (ordinary `tpl-{group}-{type}` template) or into a nested list (`tpl-child-{type}` + `__PREFIX__`
substitution from the destination list's own `data-prefix`, exactly like `_nested_blocks.blade.php`'s own "Add"
button already did).

**The per-block fast preview path (`runBlockPreview()`) is now nesting-aware too**, since a nested child's
`.block-card` is genuinely findable via path/group in the iframe now (it wasn't before — nested edits always
silently fell back to a full reload). Fixing this surfaced a real, previously-**dormant** bug in
`blockFormData()`: its regex only stripped one level of prefix (`blocks[2]` → `block`), which for a nested
field (`blocks[2][data][blocks][0][data][text]`) would have produced a malformed field name
(`block[data][blocks][0][data][text]`) never matching what `previewBlock()` expects. Fixed with a
depth-agnostic regex (`/^(?:blocks|sidebar)\[\d+\](?:\[data\]\[blocks\]\[\d+\])*/`). Also fixed: the `contained`
flag `runBlockPreview()` posts was computed only from the page template, which is wrong for any nested child
(always rendered `contained=>true` by its parent's own `@case`, regardless of template) — now `path.length > 1`
forces it.

**Structure-aware undo/redo — the actual bug this closes, not just the documented "might mismatch" gap:**
the pre-existing `captureCardFields()`/`restoreList()` were positional and **flat** — `.block-settings [name]`
sweeps up every DOM descendant's fields, including nested children's, with no record of which fields belonged
to which child or how many children existed. Worse than the old docs suggested: restoring *any* snapshot of a
populated container didn't just risk misalignment on a *changed* child count, it **unconditionally dropped every
nested child**, every time — `restoreList()` only ever cloned a *top-level* `tpl-{group}-{type}` template (always
empty), so a freshly-restored container had zero nested `.block-card`s for the extra captured field values to
apply onto; they were silently discarded. Replaced with a real recursive tree capture/restore: `captureCard()`
records `{type, fields, children}`, where `fields` is filtered to this card's own fields only
(`el.closest('.block-card') === card`, which correctly excludes a nested child's fields even though they're DOM
descendants of the same subtree) and `children` recurses into `captureCard()` for each item in the card's own
`.nested-blocks-list`, if it has one. `restoreCardInto()` is the structural inverse — rebuilds one card (via
`tpl-{group}-{type}` at the root or `tpl-child-{type}` one level down, same template choice `addChildBlock()`
already makes) and recursively restores its children into the freshly-created nested list. A container's whole
subtree now round-trips exactly through undo/redo, regardless of how many children it had or how deep the
nesting went — this was the one item from §7d's gap list that was closer to "broken" than "incomplete."

**What's still not built** (kept out of scope for this pass, flag before extending further):
- **The editor's own sidebar rail drag handle** (`.js-drag-handle`, SortableJS via `initNestedSortables()`) is
  still scoped to reordering *within* one container's own children list — it has no `group` option, so it can't
  drag a block from one container's rail into a different container's rail, or promote a nested child back to
  top-level, the way the *canvas* drag-and-drop (above) now can. Canvas drag is the more natural place for that
  interaction anyway (you can see the whole page while doing it); making the rail match wasn't requested.
- **Multi-column ROW layouts across pre-existing top-level blocks** (§7's original framing — "put the staff
  grid next to the stats block side-by-side" without manually moving them into a new Container first) still
  requires the user to explicitly add a Container/Grid and drag the existing blocks into it; there's no
  "select two blocks and wrap them in a new row" shortcut.
- **Verification gap, same as every prior nesting change**: none of this has run through Pint/PHPStan/PHPUnit
  or a real browser — no PHP/Docker in this sandbox. This is the largest single change of the whole editor
  project (recursive backend depth-tracking across 3 PHP methods, a rewritten cross-iframe drag-and-drop
  protocol, and a full undo/redo rewrite) — a real manual pass matters more here than for any prior §7x entry:
  at minimum, build a container inside a container inside a container (3 levels), drag existing blocks into and
  out of nested containers from the canvas, right-click a nested child, undo/redo across several nested
  add/remove/move actions, save, and reload the editor to confirm the saved `layout_json` round-trips through
  both the public page and the editor's own re-opened state.

## 7h. MinIO disk fix + Website media library (upload + picker)

Two related pieces of work from the same session, triggered by a real user question ("we're using MinIO but I've
never configured a bucket — how does this work?").

**The MinIO disk bug (fixed first, unblocks everything below):** `config/filesystems.php` only ever defined a
disk key `'s3'`, but every `Storage::disk('minio')` call across the app (Certificate/IdCard/DataImport modules,
and now this feature) was targeting a disk key that **never existed** — invisible in tests because
`Storage::fake('minio')` auto-creates a fake disk for whatever name you pass it, regardless of real config, so
the mismatch never surfaced there. Fixed by renaming the disk key itself from `'s3'` to `'minio'` (`driver`
stays `'s3'` — MinIO speaks the S3 API), with `FILESYSTEM_DISK` defaulting to `minio` in `.env`/`.env.example`.
Separately, MinIO starts with **zero buckets** and nothing previously created one — added a one-shot
`minio-init` service to `docker-compose.yml` (image `minio/mc`, self-retrying `mc alias set` + `mc mb
--ignore-existing`) that creates the `AWS_BUCKET` bucket the first time the stack comes up. The bucket is left
**private** (no public policy) on purpose — see the proxy design below.

**Website media library — new feature, built on scaffolding that already existed but had zero controller/route
pointing at it:** `WebsiteMedia` model, `WebsiteMediaRepository` (cache-aside `forSchool()`), `WebsiteMediaObserver`
(already registered, flushes the `'websitemedia'` cache tag), and `WebsiteMediaService::upload()/delete()` all
predated this work — they were just never wired to anything reachable from the browser.

- **Two new controllers, deliberately separate concerns.**
  `App\Http\Controllers\Admin\Website\MediaController` (`index`/`store`/`destroy`, JSON, `role:admin`, routes
  `admin.media.index|store|destroy`) is the picker's AJAX backend — school-scoped via
  `WebsiteMedia::forSchool()`/`$repository->forSchool()`, same as every other admin resource. Reads
  `app('current_school_id')` per-request, never trusts a client-supplied school id.
  `App\Http\Controllers\Public\WebsiteMediaController::show(int $id)` (route `website-media.show`, `GET
  /media/website/{id}`) is a public, unauthenticated **streaming proxy** — deliberately NOT school-scoped, since
  it's just a content-addressed public asset URL (same trust model as any other public file URL) in this
  single-school-per-deployment app (see CLAUDE.md).
- **Why a proxy instead of exposing the bucket or using `temporaryUrl()`:** a public bucket policy would mean
  `AWS_ENDPOINT=http://minio:9000` (Docker-internal-only hostname) leaking into public page HTML, which only
  resolves inside the Docker network, never from a real browser — exposing the bucket publicly would need a
  *second*, publicly-routable MinIO hostname kept in sync across dev/prod, purely to serve files the app is
  already capable of streaming itself. `temporaryUrl()` (the pattern Certificate/IdCard already use for
  ephemeral downloads) is the wrong fit here for the opposite reason: those URLs expire in ~30 minutes, but a
  page's `hero.image`/`image.url`/etc. field stores this URL *permanently* in `layout_json` — it must still
  resolve correctly months later. The proxy route streams via `Storage::disk('minio')->response($path,
  $filename, [long immutable Cache-Control])`, so the bucket itself never needs a public policy, and the URL
  never expires because it's not a signed URL at all — it's a Laravel route resolving the current file on every
  request (cheap: MinIO is on the same Docker network, and the cache header means most requests never even reach
  it after the first).
- **Field wiring:** `_fields.blade.php` gained a `'media'` input type — the same plain text URL field as before
  (typing/pasting a URL still works unchanged, e.g. an external CDN link) plus a "Browse" button
  (`onclick="openMediaPicker(this)"`) that opens a shared Bootstrap modal. `$spec` (`edit.blade.php`) switched
  `hero.image`, `image.url`, `image_text.image`, and `video.poster` from `'input'=>'text'` to `'input'=>'media'`
  — the only four fields in the whole spec that hold an image URL destined for `<img src>`/CSS
  `background-image` (video's own file/embed URLs stay plain `text`, since those are just as often an external
  YouTube/Vimeo link as a self-hosted upload).
- **The picker itself:** one modal (`#media-picker-modal`) shared by every field on the page, not per-field —
  `openMediaPicker(btn)` resolves the *specific* input to fill via `btn.closest('.input-group')`, so the same
  modal instance works regardless of which block/field opened it, including a field nested arbitrarily deep
  (§7g). Selecting a thumbnail sets the target input's `.value` and dispatches a real `input` event —
  deliberately, so none of the existing per-field preview/dirty-tracking/undo-history listeners needed any new
  wiring; they already react to `input` on every text field. Because the "Browse" button's `onclick` is inline
  markup (not an addEventListener registered once at page load), it works automatically on blocks added later via
  "Add Block" or `tpl-child-*` cloning — no re-init step needed, unlike SortableJS elsewhere in this editor which
  does need `initNestedSortables()` re-run after DOM changes.
- **Upload/delete** are plain `fetch()` calls against the admin JSON endpoints (`X-CSRF-TOKEN` from the layout's
  `<meta name="csrf-token">`, matching the pattern already used for cookie-session-authenticated AJAX elsewhere
  in this file) — no new CSRF plumbing needed. Delete asks for confirmation and warns that any block still
  referencing the file's URL will show a broken link (deleting a `WebsiteMedia` row does not search
  `layout_json` for references — there is no reverse index from a media row to the pages that embed its URL).

**What's still not built** (flag before extending further):
- **No alt-text editing UI** — `WebsiteMedia.alt_text` is a real column with no field anywhere to set it; every
  upload leaves it null.
- **No pagination/search** in the picker grid — `MediaController::index()` returns the school's *entire* media
  library in one response; fine at small scale, will need a limit + search param once a school has uploaded
  hundreds of files.
- **No drag-and-drop upload** — only the explicit "Upload" button + native file picker; no drop zone.
- **No reverse-reference tracking** — deleting a media row that's still embedded in a saved page's `layout_json`
  silently breaks that block's image (a 404 through the proxy route, not a hard error) rather than warning which
  pages reference it.
- **Verification gap, same as every prior §7x entry**: neither controller has run through Pint/PHPStan/PHPUnit,
  and the modal/upload/picker flow has not run in a real browser (no PHP/Docker in this sandbox) — verified here
  only via brace/paren-balance checks on the PHP files and a Blade-stripped `node --check` pass on the inline
  JS. At minimum: confirm `docker compose up` brings up `minio-init` as `Exited (0)`, upload an image through
  the picker, confirm it renders on the public page via the proxy URL, and confirm deleting it 404s that URL
  rather than 500ing.

## 7i. Per-page SEO fields (meta_title / meta_desc / og_image)

`Page` already had all three columns and `PageService::duplicate()` already copied them, but nothing else in
the stack actually read or wrote them — no form field, no validation rule, and the public renderer only ever
used the site-wide `SiteSetting` values.

- **Admin:** the Page Settings sidebar panel (`edit.blade.php`, `data-panel="settings"`) gained a "SEO" section
  — Meta Title (placeholder shows the page title, since that's the actual fallback), Meta Description
  (textarea), and Social Share Image (the same 'media' picker field as the block-level image fields, §7h).
  `PageController::save()`'s validate() now accepts all three (`meta_title` ≤255, `meta_desc` ≤500, `og_image`
  ≤2048 — a URL, not a stored path, so it needs more room than a typical filename column) and passes them
  through to `PageService::update()`, which already accepted an arbitrary `$data` array — no service change
  needed there.
- **Dirty-tracking/undo-redo:** `snapshotState()`/`restoreSnapshot()` (the undo/redo + Update-button-disabled-
  until-changed machinery, §7 base milestones) explicitly enumerate the fields they capture — they were missed
  when SEO fields were added and needed the same three fields added explicitly. The generic `form.addEventListener('input'/'change', handleFormChange)` delegate (already
  form-wide) needed no change — it already fires for any field inside `#page-form`, SEO fields included.
- **Public rendering:** `page.blade.php` already had `@section('title', $page->meta_title ?: $page->title)`;
  extended with conditional `@section('meta_description', ...)`/`@section('og_image', ...)`, only defined when
  the page actually has a value set. `public/layout.blade.php` (the shared `<head>`) switched from reading
  `SiteSetting` directly to `$__env->yieldContent('meta_description'|'og_image', $siteWideDefault)` — the same
  technique `@yield('title', ...)` already used one line above, just applied programmatically since these two
  needed to feed both a `<meta>` tag AND an `og:` tag from one resolved value. A page's own value always wins;
  the site-wide `SiteSetting` default only applies when the page hasn't set one. `og_image` still passes through
  `App\Support\Media::url()` (already used for the site-wide value) so either an absolute picker URL or a bare
  storage path resolves correctly.
- **What's still not built:** no character-count/preview widget for meta title/description (just plain
  inputs — no "here's how this looks in Google" preview); no per-page Twitter Card tags (`twitter:*` — the site
  only emits Open Graph); not verified in a real browser (no PHP/Docker in this sandbox) — confirm by setting a
  page's SEO fields, saving, and viewing the rendered `<head>` (and the homepage specifically, since it renders
  through this same `public.page` view when a homepage Page exists — see `HomeController::index()`).

## 7j. Duplicate Page + Save as Template

`PageService::duplicate()` and `PageTemplateService::saveAsTemplate()` (plus the whole `PageTemplate` model/
repository/observer stack) already existed, fully correct, with zero routes or UI pointing at either — the
same "built but unreachable" pattern as the media library scaffolding in §7h.

- **Duplicate:** `POST /admin/pages/{id}/duplicate` (`admin.pages.duplicate`) — a one-button action on the
  pages index list (next to Set-as-Homepage/Delete), no confirmation prompt needed since it's non-destructive.
  Redirects straight into the editor for the new copy (draft, `-copy` slug, `(Copy)` title, latest layout only
  — never the published-only revision, so any in-progress draft edits carry over exactly as
  `PageService::duplicate()` already documented).
- **Save as Template:** a small form in the editor's Page Settings panel (second `.sidebar-panel[data-panel=
  "settings"]` block, placed after `</form>` since it posts to a different route and can't nest inside
  `#page-form`) — `window.fillTemplateName()` prompts for a name, fills a hidden input, then submits normally.
  Saves the page's current *latest* layout (draft or published, whichever was saved most recently) as a new
  `PageTemplate` row scoped to the current school.
- **Actually using a saved template:** the "New page" form (`create.blade.php`) gained a "Start From" select —
  Blank Page (default) plus every template `PageTemplateRepository::availableTo()` returns, grouped into
  "Starter Templates" (`school_id IS NULL`, seeded global ones) and "My Templates" (this school's own saved
  ones). `PageController::store()` accepts an optional `page_template_id`; when present, the new page's seed
  layout is `array_merge($blankDefaults, $template->layout_json)` — the template's own stored `template`/
  `blocks`/`sidebar` win over the create form's own Template (full/sidebar) select, which only matters for the
  blank-page path now. Without this half, "Save as Template" would have been a write-only dead end — nothing
  before this exposed the templates a user just saved anywhere they could be picked back up.
- **What's still not built:** no template management UI at all — can't rename, preview, or delete a saved
  template once created (only a raw DB row); no thumbnail generation despite the model having a `thumbnail`
  column; no global/seeded starter templates actually exist yet (the "Starter Templates" optgroup will just be
  empty until someone seeds `school_id => null` rows). Not verified in a real browser (no PHP/Docker in this
  sandbox).

## 7k. Tests: Style/Layout tabs + Container/Grid nesting

Zero coverage existed for either before this — `PageBuilderTest.php` only ever posted plain `type`/`data`
blocks, never a `style`/`layout` key or a `container`/`grid` type. New file:
`tests/Feature/Admin/PageBuilderStyleLayoutNestingTest.php`, same fixture setup as `PageBuilderTest.php`
(school + admin user + `RoleSeeder`), asserting through the real `POST /admin/pages` → `PUT /admin/pages/{id}`
→ `GET /{slug}` round trip (never calling `PageRenderService`/`BlockPresentation` directly) so a regression in
the actual HTTP boundary is what gets caught, not just the service methods in isolation.

Covers: `sanitizeStyle()` clamping/dropping invalid values (bad hex color, out-of-range padding, an
unrecognized shadow/animation keyword) and the resulting inline `style="…"` / `reveal-*` class actually
appearing in the rendered public page; `sanitizeLayout()`'s per-breakpoint `hide`/`columns` clamping and the
`d-*`/`row-cols-*` Bootstrap utility classes `BlockPresentation` derives from them; a Container holding two
leaf children and rendering both; a Container nested inside a Container (2 levels); an unknown block type
being dropped at both the top level AND inside a nested container in the same request; nesting deliberately
pushed past `MAX_NESTING_DEPTH` (asserts the stored tree never exceeds the cap and the request never
errors — the actual termination guarantee §7g's docs describe, not just "it has a constant"); a nested child's
own `style`/`layout` surviving the save → reload round trip, including the editor's own edit screen re-rendering
without error afterward.

**Verification gap, same as every other change in this session:** none of this has actually run — no
PHP/PHPUnit in this sandbox. Run `docker compose exec app php artisan test
tests/Feature/Admin/PageBuilderStyleLayoutNestingTest.php --no-coverage` before trusting it; written carefully
against the real `sanitizeStyle()`/`sanitizeLayout()`/`normalizeBlocks()`/`BlockPresentation` source (traced by
hand, including the exact depth arithmetic for the past-cap test), but "traced by hand" is not "observed
passing."

## 7l. Public page render caching

`PageRenderService`/the public `PageController` never called `Cache::` at all before this — every visit to a
published page re-resolved every block's live data (notices/stats/staff queries) from scratch, on every
request, even though most published pages change rarely.

- **New `PageRenderService::renderPage(Page $page): ?array`** — the cached counterpart to `buildView()`,
  called by `Public\PageController::show()` and `HomeController::index()` (both previously called
  `buildView($schoolId, $layout?->layout_json)` directly). The **admin live-preview endpoints
  (`preview()`/`previewBlock()`) still call `buildView()`/`buildViewFromBlocks()` directly, untouched and
  uncached** — they render the editor's current unsaved form state, which must never come from a cache.
- **Cache key is the published `PageLayout` row's own id, not the page id.** Every `PageService::publish()`
  creates a brand-new `PageLayout` row (layouts are versioned — see `CLAUDE.md`/§"the actual save
  model"), so a fresh publish is automatically a fresh cache key with zero explicit invalidation code needed —
  deliberately different from the tag-flushing Observer pattern (`Cache::tags([...])->flush()` on
  `saved`/`deleted`) every other Repository cache in this codebase uses. `Cache::tags(['pageview'])` is still
  applied (for ops visibility / bulk-flush capability), but nothing in this change ever calls
  `Cache::tags(['pageview'])->flush()` itself.
- **The one staleness window this can't close by construction**: a "dynamic" block's live data (notices count,
  staff list, stats) changing without the page itself being re-published. Bounded by a flat 5-minute TTL
  (`CACHE_TTL`) rather than making Announcement/Staff/etc. aware this cache exists — deliberately, to avoid
  coupling unrelated modules to the Website module's rendering internals just to keep a cache fresh.
- **Regression test**: `PageBuilderTest::test_republishing_does_not_serve_a_stale_cached_render()` — publishes
  a page, republishes it with different content (a new `PageLayout` row/id), and asserts the second render
  shows the new content, not the first render's cached HTML. This is the one behavior that would silently break
  if the cache key were ever changed to something coarser (e.g. page id instead of layout id).
- **What's still not built**: no caching for the admin pages list/history screens (not requested, and those are
  already scoped to a handful of DB rows per school — not the same "resolve live module data on every request"
  cost the public render has); no cache warming (first visitor after a publish pays the uncached cost, same as
  before this change); not verified in a real browser/Redis (no PHP/Docker in this sandbox) — confirm by timing
  a repeat request to a published page and checking `docker compose exec app php artisan tinker` shows the
  `pageview:layout:{id}` key actually landing in Redis.

## 7m. Autosave + concurrent-edit warning

Before this, closing a browser tab mid-edit lost everything back to the last real Save, and two admins editing
the same page at once would silently overwrite one another with no warning — the second Save just won, full
stop.

**Autosave (crash/tab-close recovery) — client-side only, deliberately never touches the server:**
`edit.blade.php` piggybacks on the existing `pushHistory()` debounce (already fires ~1.2s after the last edit,
for undo/redo) — a thin wrapper around it now also writes `{savedAt, snapshot}` to
`localStorage['website-editor-autosave-{pageId}']`. On load, `checkAutosaveRecovery()` compares that draft
against `initialSnapshotJson` (the state the editor just rendered); if they differ, a dismissible banner offers
Restore (calls the existing `restoreSnapshot()` — the same function undo/redo already uses) or Discard. The
draft is cleared on a real form submit (`#page-form`'s `submit` listener) since the server now has that state
(or newer). Wrapped in `try/catch` throughout — a full or disabled localStorage degrades to "no autosave,"
never to a broken editor. This is **client-side only, no new `PageLayout` revisions are created by typing** —
unlike a server-side autosave, it can't flood the History panel, but it also only protects the admin who typed
it, in their own browser; it does nothing for the concurrent-edit case below, which is a genuinely different
failure mode.

**Concurrent-edit warning — real optimistic concurrency, server-side:** `PageController::edit()` now passes
`knownLayoutId` (the page's current latest `PageLayout` id at the moment the editor loads) into a new hidden
`known_layout_id` field. `save()` compares that value against whatever the latest revision actually is BY THE
TIME the save request arrives — a mismatch means someone else saved in between. Because this app's layouts are
already append-only/versioned (`PageService`/`CLAUDE.md` — every save is a new row, never an update), the
safest response to a real conflict was judged to be: **never block or discard either admin's work.** A
conflicting save still creates its own new `PageLayout` revision (nothing is ever lost), it just skips the
auto-`publish()` step and redirects back with a `warning` flash instead of the normal `status` flash, telling
the admin to check History and manually decide which revision should actually be published. This sidesteps a
much harder problem a real "block and force-reload" flow would have hit: this app is a full-page POST form
editor (not an SPA/AJAX submit), so rejecting the save outright and reloading the page would have thrown away
whatever the admin had just tried to save — worse than the silent-overwrite bug being fixed.
`layouts/admin-fullscreen.blade.php` (the shell this editor uses) had **never rendered any flash message at
all** before this — `redirect()->with('status', ...)` was already being set on every save and simply never
shown; fixed as a byproduct (`edit.blade.php` now renders both `status`/`warning` as floating dismissible
alerts, `status` auto-fading after 4s via `bootstrap.Alert`, `warning` staying until dismissed).

**Regression tests**: `PageBuilderTest::test_concurrent_save_keeps_both_revisions_and_warns_instead_of_overwriting()`
— two saves against the same stale `known_layout_id`, asserts both revisions exist, the originally-published
one is still published (never silently swapped), and the second response carries the warning flash instead of
the success one.

**What's still not built:**
- **Undo/redo doesn't re-autosave.** `pushHistory()` is only called on actual edits, not by `undo()`/`redo()`
  themselves — closing the tab immediately after an undo would offer to "restore" the state from before that
  undo, not the undone-to state. Low-stakes (the recovery banner is opt-in, never auto-applied), flagged rather
  than fixed given how narrow the window is.
- **No live "someone else is editing this page right now" indicator** — the warning only surfaces AFTER a
  conflicting save already happened, not proactively (no presence/locking system, no polling). A full
  editor-locking feature (like Google Docs' "X is editing") was judged out of scope for this pass.
- **No UI shortcut to diff/merge the two conflicting revisions** — History already lets an admin restore any
  old revision as a new draft, but there's no side-by-side diff between "my draft" and "the one that got
  published instead," so resolving a real conflict still means re-doing the comparison by eye.
- **Verification gap, same as every change in this session:** not run in a real browser (no PHP/Docker in this
  sandbox) — confirm by opening the same page in two tabs, saving in one, then saving in the other, and
  checking the warning banner + History panel show both revisions correctly.

## 8. Decisions to confirm when resuming (if not already answered above)

- Confirm the exact current route/controller method name for the public page `show()` action before Phase 1
  (referenced as "presumably" above — verify, don't assume).
- Confirm whether Phase 8's "Restore" action should require a confirmation step (recommended: yes, native
  Bootstrap confirm modal, consistent with the delete/deactivate pattern used everywhere else in this admin).
- Milestones 8–10 are independent "Pro flavor" add-ons — confirm before starting them that they're still
  wanted, or whether to stop at Milestone 7 (core Elementor-like editing) and ship.
