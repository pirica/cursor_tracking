# Agent instructions

PHP dashboard for local Cursor **agent transcripts**, **Plan mode** files, and **project rules**. Primary target is Windows + Laragon; Cloud Agents use the built-in PHP server (see below).

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

- **Install** (`bash .cursor/install.sh`): ensures `php-cli` and the `php-mbstring` extension are available (idempotent). `mbstring` is required — `lib/transcript_parse.php` uses `mb_strlen`/`mb_substr`, so transcript pages fatal-error without it.
- **Start** (`bash .cursor/start.sh`): no-op; the PHP app does not require background services.
- **Terminal** preset **PHP app**: `php -S 0.0.0.0:8080 -t /workspace` — use port **8080** for smoke tests.

After boot, open **Config** (`settings.php`) if transcript/plan/rule counts are empty: set paths to this machine’s Cursor directories. On Cloud, transcripts often live under `$HOME/.cursor/projects/<slug>/agent-transcripts`; plans under `$HOME/.cursor/plans`; rules under `<repo>/.cursor/rules`.

**Open location** (`api/open_location.php`) targets Windows Explorer; on Linux it may not open a file manager. Use **Copy path** instead.

Do not expose this app on a public host without authentication — transcripts may contain secrets.
