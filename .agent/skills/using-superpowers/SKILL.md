---
name: using-superpowers
description: Use when starting any conversation - establishes how to find and use skills, requiring skill invocation before ANY response including clarifying questions
---

<SUBAGENT-STOP>
If you were dispatched as a subagent to execute a specific task, ignore this skill.
</SUBAGENT-STOP>

<EXTREMELY-IMPORTANT>
If you think there is even a 1% chance a skill might apply to what you are doing, you ABSOLUTELY MUST invoke the skill.

IF A SKILL APPLIES TO YOUR TASK, YOU DO NOT HAVE A CHOICE. YOU MUST USE IT.

This is not negotiable. You cannot rationalize your way out of this.
</EXTREMELY-IMPORTANT>

## The Rule

**Invoke relevant or requested skills BEFORE any response or action** — including clarifying questions, exploring the codebase, or checking files. If it turns out wrong for the situation, you don't have to use it.

**Before entering plan mode:** if you haven't already brainstormed, invoke the brainstorming skill first.

Then announce "Using [skill] to [purpose]" and follow the skill exactly. If it has a checklist, create a todo per item.

## Skill Priority

When multiple skills apply, process skills come first — they set the approach, then implementation skills (frontend-design, etc.) carry it out. Brainstorming and systematic-debugging are Superpowers' most common process skills, but the rule holds for any of them.

- "Let's build X" → superpowers:brainstorming first, then implementation skills.
- "Fix this bug" → superpowers:systematic-debugging first, then domain skills.

## Red Flags

These thoughts mean STOP—you're rationalizing:

| Thought | Reality |
|---------|---------|
| "This is just a simple question" | Questions are tasks. Check for skills. |
| "I need more context first" | Skill check comes BEFORE clarifying questions. |
| "Let me explore the codebase first" | Skills tell you HOW to explore. Check first. |
| "I can check git/files quickly" | Files lack conversation context. Check for skills. |
| "Let me gather information first" | Skills tell you HOW to gather information. |
| "This doesn't need a formal skill" | If a skill exists, use it. |
| "I remember this skill" | Skills evolve. Read current version. |
| "This doesn't count as a task" | Action = task. Check for skills. |
| "The skill is overkill" | Simple things become complex. Use it. |
| "I'll just do this one thing first" | Check BEFORE doing anything. |
| "This feels productive" | Undisciplined action wastes time. Skills prevent this. |
| "I know what that means" | Knowing the concept ≠ using the skill. Invoke it. |

## Platform Adaptation

If your harness appears here, read its reference file for special instructions:

- Antigravity IDE / CLI (`agy`): `references/antigravity-tools.md` *(Primary harness for Kost Asri — read this too)*
- Claude Code: `references/claude-code-tools.md`
- Codex: `references/codex-tools.md`
- Pi: `references/pi-tools.md`
- Hermes Agent: `references/hermes-tools.md`
- Muse: `references/muse-tools.md`

## Kost Asri Project Routing (`Documentation/`, `Blueprint/`, `Task/`)

In workspace `c:\xampp\htdocs\kost-asri` (**Mini ERP Kost ASRI BOARDING HOUSE**), always pair Superpowers process skills with the project's three authoritative directories:

1. **New feature, requirement change, or unspecified behavior** → invoke `brainstorming` first (reading `Documentation/PRD-Kost-Asri.md`, `Blueprint/Blueprint-Kost-Asri.md`, and relevant `Task/Task-*.md` BEFORE asking questions), then `writing-plans`.
2. **Implementing existing checklist items from `Task/Task-*.md`** (`SET-*`, `DB-*`, `DSN-*`, `BE-*`, `API-*`, `FE-*`, `SEC-*`, `TST-*`, `QA-*`):
   - If the requirement is already unambiguous in `PRD` + `Blueprint` + `Task-*.md` and the user asked directly to execute that task ID, confirm scope against those docs, invoke `writing-plans` (or `executing-plans` / `subagent-driven-development` if a granular plan already exists), and strictly enforce `test-driven-development` + `verification-before-completion`.
   - If any design choice is still open, invoke `brainstorming` on that open choice only.
3. **Bug, failing Pest test, billing/prorata mismatch, or webhook/UI issue** → invoke `systematic-debugging` first, then `test-driven-development`.
4. **Completing any task or checking `- [x]` in `Task/Task-*.md`** → invoke `verification-before-completion` first (`php artisan test`, `./vendor/bin/pint --test`, `npm.cmd run build`).

## User Instructions

User instructions (`.agent/rules/superpowers.md`, `Documentation/PRD-Kost-Asri.md`, `Blueprint/Blueprint-Kost-Asri.md`, `Task/Task-*.md`, CLAUDE.md, AGENTS.md, GEMINI.md, direct requests) take precedence over skills, which in turn override default behavior. Only skip skill workflows or instructions when your human partner has explicitly told you to.
