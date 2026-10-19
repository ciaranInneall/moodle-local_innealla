# local_innealla

Core Innealla SIS bridge for Moodle 5.2+.

Configure under **Site administration → Plugins → Local plugins → Innealla SIS bridge**:

- SIS API base URL (ending in `/api`)
- Institution id
- Shared API key (matches Hub `apiKey`)
- Enable scheduled sync

Companions are dispatched by Hub feature flags from `GET /moodle-sync/manifest`.

CLI: `php public/local/innealla/cli/sync.php`
