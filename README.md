# local_innealla — Innealla SIS bridge for Moodle™

**Requires Moodle™ 4.5–5.2+** (see `version.php`). Connect a Moodle™ site to [Innealla SIS](https://innealla.com) (redirects to https://inneall.ie/innealla). This is the **core** plugin of the Innealla SIS suite.

Moodle™ is a registered trademark of Moodle Pty Ltd. Inneall is not affiliated with or endorsed by Moodle Pty Ltd.

## How to get an Innealla SIS tenant

Innealla is a hosted SIS subscription from Inneall. To obtain a tenant:

1. Book a demo or an Innealla Discovery at https://inneall.ie/get-in-touch (or email support@inneall.ie).
2. Inneall provisions a tenant for your college and enables the Moodle™ Integration Hub add-on.
3. You receive the SIS API base URL (ending in `/api`), institution id, and shared API key.
4. Paste those into **Site administration → Plugins → Local plugins → Innealla SIS bridge**, then enable scheduled sync.

Without a tenant the plugin installs and the settings screen is available, but scheduled sync stays idle (no courses, enrolments or grades move) until the Hub credentials point at a live tenant.

## Install

1. Install into `moodle/local/innealla` (or via Moodle Marketplace ZIP).
2. Install companion plugins as needed (`local_innealla_courses`, `_enrol`, `_assess`, `_grades`, `_groups`, `_results`, plus optional companions).
3. Configure the bridge settings (API URL, institution id, API key).
4. In Innealla Integration Hub, enable the Moodle™ add-on with matching `apiKey` and `baseUrl`.

## Configuration

Under **Site administration → Plugins → Local plugins → Innealla SIS bridge**:

- SIS API base URL (ending in `/api`)
- Institution id
- Shared API key (matches Hub `apiKey`)
- Enable scheduled sync

Companions are dispatched by Hub feature flags from `GET /moodle-sync/manifest`.

CLI: `php local/innealla/cli/sync.php` (or under `public/` on Moodle™ 5).

## License

GNU GPL v3 or later. See `LICENSE` in this package.
