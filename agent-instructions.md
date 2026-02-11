# Agent Instructions: Documentation Fact-Check + Function Documentation Workflow

Use this workflow to review and improve project docs in controlled, low-risk passes.

## Goal
Create a reliable, repeatable process to:
1. Fact-check `README.md` and `documentation.md` against current code behavior.
2. Review functions across all PHP files and ensure documentation/comments are clear and accurate.

---

## Operating Rules
- Work in small scopes (one subsystem or 1–2 files per pass).
- Always provide file/line evidence for each mismatch.
- Prefer objective mismatches over style opinions.
- Commit changes in small, focused PRs.

---

## Documentation Contract (Approved Baseline)
Use this contract as the source of truth for all documentation passes.

### A) Product & Navigation Accuracy
- Docs must reflect the current plugin navigation and labels exactly (including Welcome screen and top-bar link behavior).
- No screen, button, submenu, or workflow step may be documented if it does not exist in code.
- If behavior differs by role/capability, docs must explicitly call that out.

### B) CSV & Import/Export Accuracy
- Every documented CSV header must match real template/export output exactly (name, order, casing, punctuation).
- Every documented accepted import header must match parser/validator behavior.
- Date/time format instructions in docs must match both UI guidance and parsing rules.
- Examples in README/documentation must use valid headers for the current implementation.

### C) Roles, Permissions, and Access Rules
- Manager vs Admin permissions must be documented from actual capability checks in code.
- Media Library behavior (upload/view/select scope) must match runtime filtering logic.
- Login redirects and admin-bar visibility rules must be documented exactly as implemented.

### D) Function Documentation Standard
For each important function (especially handlers/helpers):
- Public intent is documented (what it does).
- Inputs/expected data are clear (query args, POST fields, CSV fields, return shape where relevant).
- Side effects are called out (DB writes, redirects, cache busting, transient usage, file output).
- Error/validation behavior is documented where non-obvious.

### E) Evidence & Reporting Standard
Every fact-check report must include:
- ✅ Confirmed accurate items
- ❌ Mismatches
- 🔧 Exact replacement text (ready to apply)
- 📌 File + line evidence for each finding

### Contract Acceptance Criteria
The documentation is considered “in contract” only if:
1. No documented behavior contradicts code.
2. CSV docs are executable as written.
3. Role/access docs match capability checks and hooks.
4. Key functions have clear, non-misleading documentation.

---

## Phase 1 — Fact-check Docs Against Code (docs → code)
Run these passes in order.

### Pass 1A: README accuracy
Scope:
- `README.md`
- `includes/class-lllm-admin.php`
- `includes/class-lllm-import.php`
- `includes/class-lllm-shortcodes.php`

Output format:
- ✅ Accurate items
- ❌ Mismatches
- 🔧 Exact replacement text
- 📌 File+line evidence for every mismatch

### Pass 1B: CSV templates/import behavior
Scope:
- `README.md`
- `documentation.md`
- `includes/class-lllm-admin.php`
- `includes/class-lllm-import.php`

Focus:
- Template headers
- Export headers
- Accepted import headers
- Date/time format language

### Pass 1C: Roles, permissions, and access
Scope:
- `documentation.md`
- `includes/class-lllm-roles.php`
- `little-league-manager.php`
- `includes/class-lllm-admin.php`

Focus:
- Role capability table
- Media library behavior
- Login redirect behavior
- Admin-bar visibility behavior

### Pass 1D: UI flow correctness
Scope:
- `documentation.md`
- `includes/class-lllm-admin.php`

Focus:
- Screen availability
- Wizard step behavior
- Button labels/actions
- Notices and blocking conditions

---

## Phase 2 — Function Documentation Audit (code → docs/comments)
Audit files in chunks and propose/add docs where needed.

Order:
1. `little-league-manager.php`
2. `includes/class-lllm-roles.php`
3. `includes/class-lllm-import.php`
4. `includes/class-lllm-standings.php`
5. `includes/class-lllm-shortcodes.php`
6. `includes/class-lllm-admin.php` (split into sections)
7. `includes/class-lllm-migrations.php`
8. `includes/class-lllm-activator.php`

For each function, classify:
- **A: Well documented** (no change)
- **B: Needs PHPDoc** (public behavior unclear)
- **C: Needs inline comment** (non-obvious logic/edge case)
- **D: Misleading/stale docs** (update/remove)

Deliverable per file:
- Prioritized list with minimal, concrete edits.

---

## Phase 3 — Apply Fixes in Small PRs
Do not batch everything in one PR.

Recommended PR sequence:
1. README factual corrections
2. CSV/import docs corrections
3. Roles/access docs corrections
4. Function PHPDoc + inline comment cleanup by module

Each PR should include:
- Scope summary
- Evidence-backed changes only
- Syntax checks (`php -l`) for touched PHP files

---

## Phase 4 — Final Consistency Sweep
After all focused PRs merge:
- Re-run quick checks for known drift areas:
  - CSV header names/order
  - Import wizard button labels and behavior
  - Role/cap statements
  - Welcome/admin-bar/login behavior
- Ensure README and `documentation.md` agree on terminology.

Deliverable:
- Final “all checks pass” report with any remaining TODOs.

---

## Prompt Templates for Step-by-Step Execution
Use these prompts to run the workflow in controlled chunks.

### Template A — Fact-check pass
"Fact-check [DOC FILE] against [CODE FILES]. Return only factual mismatches with exact replacement text and file/line evidence."

### Template B — Function docs audit
"Audit functions in [FILE or LINE RANGE] for missing or misleading docs/comments. Classify A/B/C/D and propose minimal edits."

### Template C — Apply scoped edits
"Apply only the approved fixes from [PASS NAME], run syntax checks, and provide a compact change summary with citations."

---

## Success Criteria
- No documented behavior contradicts current code.
- CSV guidance is exact and import-compatible.
- Permission behavior is accurately documented.
- Critical handlers/helpers have clear docs/comments.
- Changes are reviewable due to small PR scope.
