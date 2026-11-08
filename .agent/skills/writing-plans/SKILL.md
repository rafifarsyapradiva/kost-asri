---
name: writing-plans
description: Use when you have a spec or requirements for a multi-step task, before touching code
---

# Writing Plans

## Overview

Write implementation plans for an engineer who has not seen this codebase or this spec. Assume they write idiomatic code in the project's language once they know the exact interface and the exact test, and that they will make a reasonable choice wherever the plan leaves one open. What they cannot know is what you decided: which files, which names and signatures, which values from the spec, which tests prove each task. Document those. Give them the whole plan as bite-sized tasks. DRY. YAGNI. TDD. Frequent commits.

**Announce at start:** "I'm using the writing-plans skill to create the implementation plan."

**Context:** If working in an isolated worktree, it should have been created via the `superpowers:using-git-worktrees` skill at execution time.

**Save plans to:** `Task/plans/YYYY-MM-DD-<feature-name>.md` (aligned with Kost Asri's `Task/` directory; always cross-reference official task IDs from `Task/Task-*.md` such as `SET-*`, `DB-*`, `DSN-*`, `BE-*`, `API-*`, `FE-*`, `SEC-*`, `TST-*`, `QA-*`, and requirement IDs `FR-*`, `BR-*`, `NFR-*` from `Documentation/PRD-Kost-Asri.md`)
- (User preferences for plan location override this default)

## Kost Asri Architectural & Global Constraints (`Blueprint/` & `Task/`)

Every plan written in `c:\xampp\htdocs\kost-asri` MUST respect the directory layout in `Blueprint/Blueprint-Kost-Asri.md §3` and include the applicable project constraints in `## Global Constraints`:
- **Backend**: Laravel 12 (PHP 8.2), `declare(strict_types=1);` in `app/Services`, `app/Actions`, `app/Support`; PSR-12 (`./vendor/bin/pint`); Thin Controller → `FormRequest` → `Policy` → `Action`/`Service` (`DB::transaction()` + `lockForUpdate()`) → Queued `Event`/`Listener`.
- **Money & Data**: Rupiah stored as integer `BIGINT` (`BR-32`, never float); statuses stored as `VARCHAR(32)` mapped to `app/Enums/*`; timezone `Asia/Jakarta` (`BR-29`); no hard-deletes on transactional tables (`reservations`, `leases`, `invoices`, `payments`, `receipts`).
- **Frontend Dual Bundle**: Public (`resources/js/public.js` + `public.css`) uses Blade + Alpine.js + Tailwind 4 (*Warm Biophilic*, zero Vue runtime, LCP < 2.5s); ERP/Wizard (`resources/js/app.js` + `app.css`) uses Vue 3 `<script setup>` + Inertia v2 + Ziggy (*Clean Bento Grid*); Rupiah & room codes (`101`–`216`) use `.num` (`tabular-nums`); status badges pair color with icon + text (`NFR-08`).
- **Security & UU PDP (`Task-Security.md`)**: Explicit `$fillable` (never `$guarded = []`); never call `env()` outside `config/*.php`; NIK encrypted + `nik_hash`; KTP re-encoded via `Intervention Image` to UUID in private disk `storage/app/private/ktp/`; Midtrans SHA-512 `hash_equals` + idempotency; DomPDF `enable_remote=false` & `enable_php=false`.
- **Testing (`Task-Testing.md`)**: Pest PHP ^3 against MariaDB `kost_asri_test` (`LazilyRefreshDatabase`), `Http::preventStrayRequests()`, `Mail::fake()`, `Notification::fake()`, `Storage::fake()`, `travelTo()` WIB, coverage ≥ 80% on `app/Services/Billing` & `app/Actions`.

## Scope Check

If the spec covers multiple independent subsystems, it should have been broken into sub-project specs during brainstorming. If it wasn't, suggest breaking this into separate plans — one per subsystem. Each plan should produce working, testable software on its own.

## File Structure

Before defining tasks, map out which files will be created or modified and what each one is responsible for. This is where decomposition decisions get locked in.

- Design units with clear boundaries and well-defined interfaces. Each file should have one clear responsibility.
- You reason best about code you can hold in context at once, and your edits are more reliable when files are focused. Prefer smaller, focused files over large ones that do too much.
- Files that change together should live together. Split by responsibility, not by technical layer.
- In existing codebases, follow established patterns. If the codebase uses large files, don't unilaterally restructure - but if a file you're modifying has grown unwieldy, including a split in the plan is reasonable.

This structure informs the task decomposition. Each task should produce self-contained changes that make sense independently.

## Task Right-Sizing

A task is the smallest unit that carries its own test cycle and is worth a
fresh reviewer's gate. When drawing task boundaries: fold setup,
configuration, scaffolding, and documentation steps into the task whose
deliverable needs them; split only where a reviewer could meaningfully
reject one task while approving its neighbor. Each task ends with an
independently testable deliverable.

## Step Granularity

**Each step is one action with a checkable result:**
- "Write the failing test" - step
- "Run it to make sure it fails" - step
- "Implement the minimal code to make the test pass" - step
- "Run the tests and make sure they pass" - step
- "Commit" - step

## Plan Document Header

**Every plan MUST start with this header:**

```markdown
# [Feature Name] Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** [One sentence describing what this builds]

**Architecture:** [2-3 sentences about approach]

**Tech Stack:** [Key technologies/libraries]

**Spec:** [path to the spec/design doc this plan implements — the plan
argues from the spec, so the spec travels with it; executors read both]

## Global Constraints

[The spec's project-wide requirements — version floors, dependency limits,
naming and copy rules, platform requirements — one line each, with exact
values copied verbatim from the spec. Every task's requirements implicitly
include this section.]

## Review Focus

[The five input classes or failure modes the spec implies but no task's
tests exercise that are most likely to bite a person using this software
— one line each, naming the input or condition and the behavior a
reasonable person would expect, most likely first. The spec is a vision
document: it says what the software must do, not everything it will
meet, and its silence on an input is not permission for that input to
break the program. Write the list here, once, with the spec in front of
you. Then, for each line, add the test that pins it to the task that
owns the code, in that task's own step style.]

---
```

## Task Structure

````markdown
### Task N: [Component Name] (`BE-0XX` / `FR-XXX` / `BR-XX`)

**Files:**
- Create: `app/Services/Billing/PriceCalculator.php`
- Modify: `app/Enums/ProrataOption.php:10-25`
- Test: `tests/Unit/Billing/PriceCalculatorTest.php`
- Checklist: `Task/Task-Backend.md` (mark `- [x]` after verification)

**Interfaces:**
- Consumes: [what this task uses from earlier tasks — exact signatures]
- Produces: [what later tasks rely on — exact method names, parameter
  and return types. A task's implementer sees only their own task; this
  block is how they learn the names and types neighboring tasks use.]

- [ ] **Step 1: Write the failing Pest test**

```php
it('calculates monthly Option 1 combined prorata for Nov 15 entry (PRD §10.3)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $quote = app(PriceCalculator::class)->quote(
        $standard, RentType::Monthly, Carbon::parse('2026-11-15'), 1, ProrataOption::Combined
    );

    expect($quote->total())->toBe(1_380_000)
        ->and($quote->minDp(30))->toBe(414_000);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PriceCalculatorTest`
Expected: FAIL with "Class App\Services\Billing\PriceCalculator not found"

- [ ] **Step 3: Implement `PriceCalculator::quote(...)` in `app/Services/Billing/PriceCalculator.php`**

One line on the approach when the signature and the test leave a choice
(which library call, which data structure); a code block only for an
algorithm they do not determine.

- [ ] **Step 4: Run test & style check to verify they pass**

Run: `php artisan test --filter=PriceCalculatorTest` and `./vendor/bin/pint --test`
Expected: PASS (0 failures, PSR-12 clean)

- [ ] **Step 5: Update `Task/Task-*.md` checkbox and commit**

```powershell
git add tests/Unit/Billing/PriceCalculatorTest.php app/Services/Billing/PriceCalculator.php Task/Task-Backend.md
git commit -m "feat(billing): implement PriceCalculator quote logic (BE-019)"
```
````

## What a Step Contains

A step is done when the implementer can write exactly one reasonable thing
from it. That is the whole requirement: unambiguous, not complete. Each kind
of step carries what makes it unambiguous and nothing more:

- **A test step:** the test's name and its assertions, as code, with the
  spec's exact values in them.
- **A code step:** the exact signature (name, parameters, return type), the
  file it lives in, and the specific values the spec pins. The implementer
  writes the body. A body appears only for an algorithm the signature and
  tests do not determine, or for exact copy the spec fixes.
- **A verification step:** the command to run and the output that means it
  passed.
- **A reference to another task:** that task's Interfaces block says what
  to use; the plan does not repeat that task's code.

A plan is the set of decisions the implementer cannot make alone. A plan
longer than the code it describes has written the code instead. Lines that
decide nothing ("TBD", "handle edge cases", "add appropriate validation",
"write tests for the above", a type or function no task defines) are the
opposite failure, and the self-review catches both.

## Self-Review

After writing the complete plan, look at the spec with fresh eyes and check the plan against it. This is a checklist you run yourself — not a subagent dispatch.

**1. Spec coverage:** Skim each section/requirement in the spec. Can you point to a task that implements it? List any gaps.

**2. Step scan:** Every step must let the implementer write exactly one reasonable thing, and no step may carry more than that: a line that decides nothing is a gap, a function body the signature and tests already determine is a transcript. Fix both.

**3. Type consistency:** Do the types, method signatures, and property names you used in later tasks match what you defined in earlier tasks? A function called `clearLayers()` in Task 3 but `clearFullLayers()` in Task 7 is a bug.

**4. Review Focus:** For each input class or failure mode the spec implies, is there a task whose tests exercise it? The five uncovered ones most likely to bite a person go in the Review Focus section, and each line there gets its test added to the owning task. An empty section means you checked and found none, not that you skipped the check.

**5. Proportion:** Compare the plan's length to the spec's. A plan several times longer than the spec it implements is a transcript of the program, not a plan. If code blocks are most of the document, replace bodies with signatures, test names and assertions, and check that each step is still unambiguous.

If you find issues, fix them inline. No need to re-review — just fix and move on. If you find a spec requirement with no task, add the task.

## Execution Handoff

After saving and self-reviewing the plan, link it for your human partner
to read. If they have already explicitly supplied an execution method, ask
them to review the plan and confirm it captures what they want; wait for that
review before implementation, then use the preserved method. Otherwise, ask
them to review the plan and choose an execution method before implementation.

**When no execution method has already been supplied:**

**"Plan complete and saved to `Task/plans/<filename>.md`. Please review the plan. Which execution approach would you prefer?**

- **Subagent-driven** - A fresh subagent implements each task and a fresh reviewer checks it before the next one starts, then a whole-branch review at the end. Most thorough; costs a fresh context per task and per review.
- **Native** - I implement every task myself in this session, the way this harness runs work, then one fresh reviewer on the most capable model checks the whole branch. Cheapest and fastest; no independent review until the end. Runs well with a mid-tier session model, since the plan carries the design.

**For this plan I recommend <one of the two>, because <one sentence from the plan: how much the tasks depend on each other's interfaces, how many there are, what a shipped mistake would cost>. Does the plan capture what you want, and which approach should we use?"**

**When an execution method has already been supplied:**

**"Plan complete and saved to `Task/plans/<filename>.md`. Please review the plan. Does it capture what you want?"**

**If Subagent-driven chosen:**
- **REQUIRED SUB-SKILL:** Use superpowers:subagent-driven-development

**If Native chosen:**
- **REQUIRED SUB-SKILL:** Use superpowers:executing-plans
