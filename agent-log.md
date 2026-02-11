# Agent Progress Log

## Phase 1 Progress

### Pass 1A — README Accuracy (Started/Completed)
**Status:** Completed  
**Scope reviewed:**
- `README.md`
- `includes/class-lllm-admin.php`
- `includes/class-lllm-import.php`
- `includes/class-lllm-shortcodes.php`

#### ✅ Accurate items
- Full Schedule and Score Update examples in README currently match the implemented header order (`away_team_code` before `home_team_code`, and `away_score` before `home_score`).
- Teams template in README currently shows `franchise_code`, which matches current template generation.

#### ❌ Mismatches found
1. README states that **Divisions, Franchises, and Teams** screens all include CSV helpers to download templates and validate before import.
   - **Code reality:**
     - Divisions screen has validate + import forms.
     - Teams screen has template + import only (no validate form).
     - Franchises CSV import block is currently disabled behind `$show_franchises_csv_import = false`.

#### 🔧 Suggested replacement text (not yet applied)
Replace this README sentence:
> "The **Divisions**, **Franchises**, and **Teams** admin screens include CSV helpers that let you download templates and validate your CSV before importing."

With:
> "The **Divisions** and **Teams** admin screens include CSV helpers for template download and import. Divisions include a separate CSV validation step; Teams currently import directly from template format."

#### 📌 Evidence references
- README claim line: `README.md` line 73.
- Divisions validate/import UI: `includes/class-lllm-admin.php` lines 495–510.
- Teams import-only UI: `includes/class-lllm-admin.php` lines 710–720.
- Franchises CSV import disabled flag: `includes/class-lllm-admin.php` lines 615–636.

---

## Next Step
Proceed to **Pass 1B: CSV templates/import behavior** and produce mismatch list in same format.
