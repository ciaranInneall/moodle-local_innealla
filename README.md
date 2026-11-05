# local_innealla — Innealla SIS bridge for Moodle

**Requires Moodle 4.5–5.2+** (see `version.php`). Connect Moodle to [Innealla SIS](https://innealla.com). This is the **core** plugin of the Innealla SIS suite.

## Install

1. Install into `moodle/local/innealla` (or via Moodle Marketplace ZIP).
2. Install companion plugins as needed (`local_innealla_courses`, `_enrol`, `_assess`, `_grades`, `_groups`, `_results`, plus optional companions).
3. Configure: Site administration → Plugins → Local plugins → Innealla SIS bridge.
4. In Innealla Integration Hub, enable the Moodle add-on with matching `apiKey` and `baseUrl`.

## Configuration

Under **Site administration → Plugins → Local plugins → Innealla SIS bridge**:

- SIS API base URL (ending in `/api`)
- Institution id
- Shared API key (matches Hub `apiKey`)
- Enable scheduled sync

Companions are dispatched by Hub feature flags from `GET /moodle-sync/manifest`.

CLI: `php admin/cli/` path equivalent — `php local/innealla/cli/sync.php` (or under `public/` on Moodle 5).

## License

GNU GPL v3 or later. See `LICENSE` in this package.
