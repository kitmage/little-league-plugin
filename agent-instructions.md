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

## Phase 0 — Define the Documentation Contract
Before editing docs, confirm what “accurate” means.

Checklist:
- README reflects current admin screens and real behavior.
- CSV headers in docs match template/export and accepted import headers.
- Roles/capabilities documented correctly (Manager vs Admin).
- Import wizard behavior and error handling are accurate.
- Shortcode examples match current shortcode implementation.
- Function-level docs exist for key handlers/helpers with non-obvious behavior.

Deliverable:
- A short “contract” checklist to validate in each subsequent pass.

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
