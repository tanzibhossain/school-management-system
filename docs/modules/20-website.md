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

## Services & Business Rules
- Each save creates a new versioned row for layout-related records.
- Public endpoints are designed for site consumption and do not require the school dashboard session.
- The module is intentionally content-driven and versioned.

## Integration Points
- Consumes school data and announcements.
- Supports public views for academic routine, notices, and school statistics.
