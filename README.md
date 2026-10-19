# local_innealla — Innealla SIS bridge for Moodle

Connect Moodle to [Innealla SIS](https://innealla.com). This is the **core** plugin of the Innealla SIS suite.

## Install

1. Install into `moodle/local/innealla` (or via Moodle Marketplace ZIP).
2. Install companion plugins as needed (`local_innealla_courses`, `_enrol`, `_assess`, `_grades`, `_groups`, `_results`, plus optional companions).
3. Configure: Site administration → Plugins → Local plugins → Innealla SIS bridge.
4. In Innealla Integration Hub, enable the Moodle add-on with matching `apiKey` and `baseUrl`.

## Suite (companions)

Companions are separate Moodle plugins (same pattern as ZiLink / Mahara sets). Each declares a dependency on `local_innealla`.

## Support

- Email: support@inneall.com
- Issues: use this repository’s issue tracker

## License

GNU GPL v3 or later.
