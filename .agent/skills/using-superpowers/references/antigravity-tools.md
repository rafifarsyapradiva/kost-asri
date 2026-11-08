# Antigravity IDE & CLI (`agy`) Tool Mapping — Kost Asri Workspace

Skills speak in generic actions ("dispatch a subagent", "create a todo", "read a file", "run bash script"). In **Antigravity IDE / Antigravity 2.0** on Windows (`c:\xampp\htdocs\kost-asri`), map those actions to the exact native tools and commands below.

## 1. Native Tool Mapping

| Action skills request | Antigravity IDE / `agy` equivalent |
|---|---|
| Read a file | `read_file` (supports `StartLine` / `EndLine` for bounded reads) |
| Create or overwrite a file | `write_file` (`Overwrite: true` when replacing; omit `ArtifactMetadata` for project files) |
| Edit an existing file | `edit_file` (exact `Target` and `Replacement`) |
| Run shell / test / git commands | `run_command` (runs Windows **PowerShell** in `c:\xampp\htdocs\kost-asri`) |
| Dispatch an implementer subagent | `invoke_subagent` with `TypeName: "self"`, explicit `Model` (`"flash_lite"`, `"flash"`, `"pro"`, or `"inherit"`), and `Workspace` (`"inherit"`, `"branch"`, or `"share"`) |
| Dispatch a read-only reviewer / analyst subagent | `invoke_subagent` with `TypeName: "research"` and explicit `Model` |
| Resume an existing subagent (e.g., fix loop rounds 1–3) | `send_message` with `Recipient: "<conversationId>"` returned by `invoke_subagent` |
| List or terminate subagents | `manage_subagents` (`Action: "list"` / `"kill"` / `"kill_all"`) |
| Task tracking ("create a todo", "mark complete") | Maintain a **task artifact** in `<appDataDir>\brain\<conversation-id>\task.md` via `write_file` / `edit_file` with `ArtifactMetadata` — **plus** sync official task checkboxes in `c:\xampp\htdocs\kost-asri\Task\Task-*.md` when verified. **Never** use `manage_task` for checklists (`manage_task` controls background processes). |
| Ask multiple-choice questions | `ask_question` (for structured choices) or plain text (for single open Socratic questions in `brainstorming`) |

## 2. Task Tracking in Antigravity IDE

Antigravity has **no `TodoWrite` tool** (`manage_task` only manages background shell/timer tasks). Whenever a skill says "create a todo per item/task":

1. Maintain `<appDataDir>\brain\<conversation-id>\task.md` using `write_file` (with `ArtifactMetadata: { Summary, UserFacing: true, RequestFeedback: false }`) and update `- [ ]` → `- [x]` via `edit_file` as steps complete.
2. When executing implementation plans (`executing-plans` / `subagent-driven-development`), also keep the persistent ledger at `.superpowers/sdd/<plan-slug>/progress.md` so progress survives context compaction.
3. When a Kost Asri checklist item in `Task/Task-*.md` (`SET-*`, `DB-*`, `DSN-*`, `BE-*`, `API-*`, `FE-*`, `SEC-*`, `TST-*`, `QA-*`) passes `verification-before-completion`, mark `- [x]` in that `Task/Task-*.md` file.

## 3. Windows PowerShell + XAMPP Execution Rules (`Task/Task-Setup.md`)

`run_command` executes in **Windows PowerShell** where bare `bash` is not on `PATH` and `npm.ps1` may be blocked by `ExecutionPolicy`:

- **Superpowers Bash helper scripts** (`sdd-workspace`, `task-brief`, `task-start`, `task-done`, `review-package`):
  ```powershell
  & "C:\Program Files\Git\bin\bash.exe" ".agent/skills/subagent-driven-development/scripts/sdd-workspace" "Task/plans/2026-11-08-example.md"
  ```
- **PHP & Laravel Artisan** (XAMPP PHP 8.2.12):
  ```powershell
  php artisan test
  ./vendor/bin/pint --test
  ```
- **NPM & Vite** (always use `.cmd` shim on PowerShell per `Task-Setup.md §2.2`):
  ```powershell
  npm.cmd install
  npm.cmd run build
  ```
- **MariaDB (`kost_asri` & `kost_asri_test`)**:
  ```powershell
  & "c:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"
  ```
