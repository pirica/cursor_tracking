# Cursor tracker (chats + plans + rules)

Local PHP dashboard for **Cursor agent transcripts**, **Plan mode** files, and **project rules**.

**URL (Laragon):** open in a new browser tab: [http://localhost/tracking_cursor/](http://localhost/tracking_cursor/)

## Pages

| Page | URL |
|------|-----|
| Home | [http://localhost/tracking_cursor/](http://localhost/tracking_cursor/) |
| Chats | [http://localhost/tracking_cursor/chats.php](http://localhost/tracking_cursor/chats.php) |
| Plans | [http://localhost/tracking_cursor/plans.php](http://localhost/tracking_cursor/plans.php) |
| Rules | [http://localhost/tracking_cursor/rules.php](http://localhost/tracking_cursor/rules.php) |
| Config | [http://localhost/tracking_cursor/settings.php](http://localhost/tracking_cursor/settings.php) |
| Chat detail | `chat.php?id={uuid}` |
| Plan detail | `plan.php?f={basename}.plan.md` |
| Rule detail | `rule.php?f={basename}.mdc` |

## What it does

### Chats

- Scans read-only JSONL under your Cursor project `agent-transcripts` folder
- Lists parent chats, nested subagents, tracking (star, status, notes)
- Tracking stored in `data/tracking.json`

### Plans

- Lists `*.plan.md` from `%USERPROFILE%\.cursor\plans\` (config: `plans_dir`)
- Parses frontmatter (`name`, `overview`, `todos`) and shows full plan body on detail page

### Rules

- Lists `*.mdc` from your repo `.cursor/rules` folder (config: `rules_dir`; default points at `it-management\.cursor\rules`)
- Shows frontmatter `description` / `alwaysApply` and full file on the detail page
- **Open location** / **Copy path** like Plans
- **Delete** removes the `.mdc` file from disk (list + detail), same as Plans

## Setup

**Requires PHP 7.4+** (no Composer extensions beyond what Laragon/Apache already loads, e.g. `com_dotnet` for Explorer on Windows).

1. Laragon serves `www` — app lives at `www/tracking_cursor/`.
2. Paths: use **[Config](http://localhost/tracking_cursor/settings.php)** to edit and save (stored in `data/local_config.json`), or edit defaults in `config.php`.
   - `transcripts_dir` — your project `agent-transcripts` path
   - `plans_dir` — e.g. `C:\Users\YOU\.cursor\plans`
   - `rules_dir` — e.g. `C:\…\it-management\.cursor\rules`
3. `data/.htaccess` blocks direct web access to `tracking.json`.

## Privacy

Transcripts and plans may contain secrets. **Local-only** — do not expose on a public host without auth.

## Open location (Windows / Laragon)

Browsers cannot open `file://` from `http://localhost`. **Open location** calls PHP to run Explorer.

If Explorer does not appear (common when Apache runs as a Windows **service** without a desktop):

1. **Path is copied** when you click Open location — press **Win+E**, paste into the address bar, Enter.
2. In Laragon: use **Apache** started from the Laragon app (not a separate Windows service).
3. In Laragon **PHP → php.ini** (Apache’s PHP), enable **`extension=com_dotnet`** and restart Apache — this improves Explorer launch via `WScript.Shell`.

## Git

Outside the `it-management` repository unless you version this folder separately.
