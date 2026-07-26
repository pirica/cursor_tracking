# Agent instructions

PHP dashboard for local Cursor **agent transcripts**, **Plan mode** files, and **project rules**. Primary target is Windows + Laragon; Cloud Agents use the built-in PHP server (see below).

**Runtime: PHP 7.4+** (e.g. Laragon’s default). Keep all PHP compatible with 7.4 — no PHP 8-only syntax (`match`, nullsafe `?->`, union/`mixed` types, attributes, enums, constructor property promotion, `str_contains` / `str_starts_with` / `str_ends_with`, etc.). Typed properties, `?type`, and `void` return types are fine.

## Commands

| Task | Command |
|------|---------|
| PHP syntax check | `find . -name '*.php' -not -path './data/*' -print0 \| xargs -0 -n1 php -l` |
| Run locally (Linux / Cloud) | `php -S 127.0.0.1:8080 -t .` then open `http://127.0.0.1:8080/` |
| Laragon (Windows) | Serve from `www/tracking_cursor/` — see [README.md](README.md) |

No Composer, npm, or test suite in this repo.

## Configuration

- Defaults: `config.php` (auto-detects common Cursor paths on Linux/macOS; generic placeholders on Windows without overrides).
- Overrides: `data/local_config.json` (edited via **Config** / `settings.php`, or committed for your machine). See `data/local_config.json.example` for a template.

Keys: `project_label`, `transcripts_dir`, `plans_dir`, `rules_dir`, `tracking_file`.

## Cursor Cloud specific instructions

Cloud Agents should use the committed `.cursor/environment.json`:

- **Install** (`bash .cursor/install.sh`): ensures `php-cli` is available (idempotent).
- **Start** (`bash .cursor/start.sh`): no-op; the PHP app does not require background services.
- **Terminal** preset **PHP app**: `php -S 0.0.0.0:8080 -t /workspace` — use port **8080** for smoke tests.

After boot, open **Config** (`settings.php`) if transcript/plan/rule counts are empty: set paths to this machine’s Cursor directories. On Cloud, transcripts often live under `$HOME/.cursor/projects/<slug>/agent-transcripts`; plans under `$HOME/.cursor/plans`; rules under `<repo>/.cursor/rules`.

**Open location** (`api/open_location.php`) targets Windows Explorer; on Linux it may not open a file manager. Use **Copy path** instead.

Do not expose this app on a public host without authentication — transcripts may contain secrets.
