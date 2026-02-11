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

### Pass 1C — Roles, Permissions, and Access (Started/Completed)
**Status:** Completed  
**Scope reviewed:**
- `documentation.md`
- `includes/class-lllm-roles.php`
- `little-league-manager.php`
- `includes/class-lllm-admin.php`

#### ✅ Accurate items
- Core custom capabilities documented in section 2 are present in code (`lllm_manage_seasons`, `lllm_manage_divisions`, `lllm_manage_teams`, `lllm_manage_games`, `lllm_import_csv`, `lllm_view_logs`).
- Admin and Manager both receive plugin capabilities via role sync.

#### ❌ Mismatches found
1. `documentation.md` says Managers see only a top-level menu named **Little League**.
   - **Code reality:** top-level menu label is **League Manager**.

2. `documentation.md` says standard WP menus are hidden for Managers (Posts/Pages/Plugins/etc.).
   - **Code reality:** no menu-hiding implementation exists for Managers.

3. `documentation.md` capability list omits newer capabilities that are active in code:
   - `upload_files`
   - `lllm_manage_media_library`

4. Role/access docs do not currently document implemented behavior for:
   - admin-bar link (`⚾ League Manager`) visibility based on `lllm_manage_seasons`
   - manager login redirect to Welcome
   - manager sitewide media-library query behavior via hooks

#### 🔧 Suggested replacement text (not yet applied)
- In roles/UI section, replace:
  > "Managers see only a top-level menu: **Little League** with the plugin pages."
  with:
  > "Managers see the plugin top-level menu: **League Manager** with the plugin pages."

- Replace/harden menu-visibility claim:
  > "Hide standard WP menus for Managers: Posts, Pages, Comments, Appearance, Plugins, Tools, Settings, etc."
  with:
  > "Managers are intended to work primarily in League Manager screens. (If strict WP menu hiding is required, it should be explicitly implemented and documented.)"

- Add missing capabilities to section 2 list:
  - `upload_files`
  - `lllm_manage_media_library`

- Add a short "Access behavior" subsection documenting:
  - admin-bar node `⚾ League Manager` is shown to logged-in users with `lllm_manage_seasons`
  - users with role `lllm_manager` are redirected on login to `admin.php?page=lllm-welcome`
  - manager media-library hooks allow sitewide browsing/selecting in modal/list views

#### 📌 Evidence references
- Top-level menu label in code: `includes/class-lllm-admin.php` lines 9–12.
- Docs "Little League" claim: `documentation.md` line 83.
- Docs menu-hiding claim: `documentation.md` line 82.
- Capabilities in code include media caps: `includes/class-lllm-roles.php` lines 16–17.
- Admin-bar/link and login redirect hooks: `little-league-manager.php` lines 25–27 and 71–78.
- Media library hook behavior for managers: `little-league-manager.php` lines 84–120.

---

## Next Step
Proceed to **Pass 1D: UI flow correctness** and produce mismatch list in same format.

### Pass 1D — UI Flow Correctness (Started/Completed)
**Status:** Completed  
**Scope reviewed:**
- `documentation.md`
- `includes/class-lllm-admin.php`

#### ✅ Accurate items
- Games screen documents Away/Home ordering and score column presence, which matches the current table layout.
- Import Wizard still uses step-based flow with type selection and upload/validate/review behavior.

#### ❌ Mismatches found
1. Teams screen docs describe a **Clone assignments from another Division** action/modal.
   - **Code reality:** only Assign Selected / Remove Selected actions are present; no clone action exists.

2. Import Wizard Step 2 docs still say **Download Current Games CSV** appears on the upload screen.
   - **Code reality:** Step 2 shows only **Download Template** and file upload/Validate.

3. Games screen docs describe **Quick edit modal**.
   - **Code reality:** quick edit is an inline per-row form toggled by an **Edit** button (no modal).

4. Import Wizard error-state docs list a **Back** button.
   - **Code reality:** button label is **Try Again** (links back to step 2).

5. Import Wizard docs include a dedicated **Step 4: Success** screen.
   - **Code reality:** flow is step-based with review/commit handlers, but no separate documented render block for a "Step 4: Success" page in `render_import_wizard_inline`.

#### 🔧 Suggested replacement text (not yet applied)
- Replace clone-related Teams screen lines with:
  > "Buttons: Assign Selected Franchises, Remove Selected Franchises."

- Replace Step 2 buttons list entry:
  > "Download Template + Download Current Games CSV"
  with:
  > "Download Template"

- Replace "Quick edit modal" wording with:
  > "Inline quick edit (per row) revealed by Edit button: status + scores + notes."

- Replace error-state "Back" button text with:
  > "Try Again"

- Replace "Step 4: Success" section with:
  > "After commit, users are redirected back to Games with an import-complete notice."

#### 📌 Evidence references
- Teams screen actions present: `includes/class-lllm-admin.php` lines 722–727 and 757–760.
- No clone UI in Teams render section: `includes/class-lllm-admin.php` lines 641–778.
- Import Wizard step 2 buttons: `includes/class-lllm-admin.php` lines 957–966.
- Games quick edit is inline/toggled (not modal): `includes/class-lllm-admin.php` lines 859–877.
- Error-state retry button label: `includes/class-lllm-admin.php` lines 998–1002.
- Documentation clone claims: `documentation.md` lines 763–768.
- Documentation Step 2 CSV button claim: `documentation.md` lines 314 and 848–850.
- Documentation quick edit modal claim: `documentation.md` line 451.
- Documentation error "Back" button claim: `documentation.md` line 874.
- Documentation Step 4 success section: `documentation.md` lines 884–897.

---

## Next Step
Phase 1 fact-check passes complete (1A–1D). Next: prepare scoped doc-fix PRs per Phase 3 sequence.

## Phase 2 Progress

### Function Documentation Audit — Chunk 1 (`little-league-manager.php`, `includes/class-lllm-roles.php`)
**Status:** Completed  
**Scope reviewed:**
- `little-league-manager.php`
- `includes/class-lllm-roles.php`

#### Classification summary
- **A (well documented):** none prior to this pass
- **B (needs PHPDoc):**
  - `autoload_lllm`
  - `lllm_maybe_upgrade`
  - `lllm_add_welcome_admin_bar_link`
  - `lllm_manager_login_redirect`
  - `lllm_manager_media_library_query`
  - `lllm_manager_media_library_list_query`
  - `LLLM_Roles::get_caps`
  - `LLLM_Roles::sync_roles`
- **C (needs inline comment):** none required beyond existing critical comment in upgrade flow
- **D (misleading/stale docs):** none found

#### Applied edits
- Added PHPDoc blocks for all functions in this chunk with intent, parameters, return types, and key side-effects where relevant.
- Preserved runtime behavior (documentation-only change).

#### Next Step
Proceed to Phase 2 Chunk 2:
- `includes/class-lllm-import.php`

### Function Documentation Audit — Chunk 2 (`includes/class-lllm-import.php`)
**Status:** Completed  
**Scope reviewed:**
- `includes/class-lllm-import.php`

#### Classification summary
- **A (well documented):** none prior to this pass
- **B (needs PHPDoc):**
  - `LLLM_Import::normalize_csv_header`
  - `LLLM_Import::get_import_types`
  - `LLLM_Import::parse_csv`
  - `LLLM_Import::get_upload_dir`
  - `LLLM_Import::save_error_report`
  - `LLLM_Import::generate_game_uid`
  - `LLLM_Import::unique_game_uid`
  - `LLLM_Import::get_season_timezone`
  - `LLLM_Import::parse_datetime_to_utc`
- **C (needs inline comment):** none required
- **D (misleading/stale docs):** none found

#### Applied edits
- Added PHPDoc blocks for every method in `LLLM_Import` with clear method intent.
- Documented parameter and return contracts, including `WP_Error`/array return shape for CSV parsing and `string|false` for datetime parsing failures.
- Documented side effects where relevant (upload directory creation, DB uniqueness lookup, report file output).

#### Next Step
Proceed to Phase 2 Chunk 3:
- `includes/class-lllm-standings.php`

### Function Documentation Audit — Chunk 3 (`includes/class-lllm-standings.php`)
**Status:** Completed  
**Scope reviewed:**
- `includes/class-lllm-standings.php`

#### Classification summary
- **A (well documented):** none prior to this pass
- **B (needs PHPDoc):**
  - `LLLM_Standings::get_cache_key`
  - `LLLM_Standings::bust_cache`
  - `LLLM_Standings::get_standings`
- **C (needs inline comment):**
  - Tie-break sorting sequence in standings `usort` comparator
- **D (misleading/stale docs):** none found

#### Applied edits
- Added PHPDoc blocks to all `LLLM_Standings` methods, including cache behavior and data-shape expectations.
- Added an inline comment that documents the exact tie-break precedence used for standings ordering.
- Preserved runtime behavior (documentation-only changes).

#### Next Step
Proceed to Phase 2 Chunk 4:
- `includes/class-lllm-shortcodes.php`

### Function Documentation Audit — Chunk 4 (`includes/class-lllm-shortcodes.php`)
**Status:** Completed  
**Scope reviewed:**
- `includes/class-lllm-shortcodes.php`

#### Classification summary
- **A (well documented):** none prior to this pass
- **B (needs PHPDoc):**
  - `LLLM_Shortcodes::register`
  - `LLLM_Shortcodes::get_season_by_slug`
  - `LLLM_Shortcodes::get_division_by_slug`
  - `LLLM_Shortcodes::get_active_season`
  - `LLLM_Shortcodes::get_first_division`
  - `LLLM_Shortcodes::resolve_context`
  - `LLLM_Shortcodes::render_schedule`
  - `LLLM_Shortcodes::render_standings`
  - `LLLM_Shortcodes::render_teams`
- **C (needs inline comment):** none required
- **D (misleading/stale docs):** none found

#### Applied edits
- Added PHPDoc blocks for all shortcode class methods.
- Documented shortcode attributes and fallback resolution behavior for season/division context.
- Documented return contracts and key data dependencies for each renderer.
- Preserved runtime behavior (documentation-only changes).

#### Next Step
Proceed to Phase 2 Chunk 5:
- `includes/class-lllm-admin.php` (split into sections)
