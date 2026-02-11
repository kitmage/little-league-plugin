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

### Pass 1B — CSV Templates / Import-Export Behavior (Started/Completed)
**Status:** Completed  
**Scope reviewed:**
- `README.md`
- `documentation.md`
- `includes/class-lllm-admin.php`
- `includes/class-lllm-import.php`

#### ✅ Accurate items
- Full Schedule template/export headers in README match code (`start_date(mm/dd/yyyy)`, `start_time(24HR)`, away-before-home team and score ordering).
- Score Update template headers in README match code (`game_uid,away_score,home_score,status,notes`).
- Import validator required headers align with parser-normalized keys (`start_date`, `start_time`, etc.), and parser normalization supports labeled date/time headers with parentheses.

#### ❌ Mismatches found
1. `documentation.md` export-format section still documents old header names/order.
   - It currently states export includes:
     - `start_date`, `start_time`, `home_team_code`, `away_team_code`, `home_score`, `away_score`
   - Code currently exports/lists:
     - `start_date(mm/dd/yyyy)`, `start_time(24HR)`, `away_team_code`, `home_team_code`, `away_score`, `home_score`

#### 🔧 Suggested replacement text (not yet applied)
Replace this line in `documentation.md` section 9.4:
> `* game_uid, start_date, start_time, location, home_team_code, away_team_code, status, home_score, away_score, notes`

With:
> `* game_uid, start_date(mm/dd/yyyy), start_time(24HR), location, away_team_code, home_team_code, status, away_score, home_score, notes`

#### 📌 Evidence references
- Current export headers in code: `includes/class-lllm-admin.php` line 1467.
- Current full-schedule template headers in code: `includes/class-lllm-admin.php` line 1426.
- Import required headers (canonical keys): `includes/class-lllm-admin.php` lines 2152–2155.
- Header normalization support for labeled date/time keys: `includes/class-lllm-import.php` lines 8–15 and 39.
- README full-schedule headers (accurate): `README.md` lines 43 and 49.
- README score-update headers (accurate): `README.md` lines 60 and 66.
- Outdated documentation export-format line: `documentation.md` line 419.

---

## Next Step
Proceed to **Pass 1C: Roles, permissions, and access** and produce mismatch list in same format.
