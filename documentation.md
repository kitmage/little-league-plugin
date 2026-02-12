# Project Summary

We’re building a WordPress plugin that lets our Little League website show schedules and standings, while keeping the volunteer “Manager” job dead simple.

Here’s how it works:

* We set up **Seasons** (like “Spring 2026”) and **Divisions** within each season (like “8U” and “9U”).
* We create franchises once (name + logo). Each franchise also gets a simple **Franchise Code** (like `dirtbags`) that never changes.
* When a franchise participates in a specific division and season, the system creates a **Team** behind the scenes. This is important because it guarantees that the 8U Dirtbags’ stats never mix with the 9U Dirtbags’ stats, and nothing carries over between seasons unless we intentionally reuse franchise identities.
* Managers upload games by CSV using a guided wizard. The wizard always follows the same safe flow: **Upload → Validate → Preview → Import**.

  * If anything is inconsistent (like a wrong team code or a bad date format), the import is rejected and the Manager gets a clear error report.
* After the initial schedule import, Managers do weekly updates using an even simpler “Score Update” CSV. This file uses a system-generated **Game ID** (like `G8K4Q2M9T1A3`) so nobody is typing team names and accidentally creating mismatches.
* The website then displays:

  * **Schedules** (upcoming games, results, statuses like scheduled/played/postponed/canceled)
  * **Standings** (wins/losses/ties, runs for/against, run differential, win %), calculated automatically from played games only.

The end result: a clean, reliable system that volunteers can run without breaking data, and a solid foundation for future enhancements (playoffs, head-to-head tie breakers, locations list, etc.) if we ever want them.

---

# Little League League Manager (LLLM) — Developer Handoff Documentation

**Approach chosen:** Custom plugin + custom relational DB tables + guided “wizard” admin UI + strict CSV imports (validate → preview → commit) + “score update” CSV that uses internal game IDs.
**Top priorities:** Dad-proofing, stability, simplicity for Managers, data integrity (no mixed stats across divisions/seasons), and clean foundation for front-end rendering.

---

## 0) Glossary (use consistently in UI + code)

* **Season**: e.g., “Spring 2026”
* **Division (Season-Division)**: a division within a season, e.g., “8U” inside “Spring 2026”
* **Franchise**: reusable team identity, e.g., “Dirtbags” + logo
* **Team (Season-Division-Team)**: unique stats-safe team entity in a specific division of a specific season (this is what games and standings reference)
* **Game**: scheduled/played/canceled/postponed event between two Teams

---

## 1) Goals and non-goals

### Goals (must ship)

1. Managers can create Seasons, Divisions, Franchises, and assign franchises to divisions using simplified screens (no WP post editor).
2. Managers can import games via CSV with a guided wizard:

   * upload → validate → preview changes → commit (atomic)
3. System rejects messy uploads (unknown team codes, invalid statuses, bad date formats, duplicate games, etc.) with a clear row-level error report.
4. Managers can do ongoing weekly updates via a **Score Update CSV** using `game_uid` so names never mismatch.
5. Divisions, Franchises, and Teams screens include CSV templates plus a validation step before import.
5. Standings compute per Season-Division only, using played games only.
6. Front-end shortcodes render schedules and standings.

### Non-goals (explicitly out of scope for v1)

* Live “in-progress” scoring / play-by-play
* Head-to-head tie-breaker rules (optional future)
* Cross-division games counting toward standings (future)
* Advanced/custom playoff formats (future)
* Automated sync with 3rd-party scheduling apps

---

## 2) Users, roles, and permissions

### Roles

* **Administrator**: full access
* **Managers** (custom role): only league-related screens + CSV import; no posts/pages/plugins

### Capabilities (custom)

* `lllm_manage_seasons`
* `lllm_manage_divisions`
* `lllm_manage_teams`
* `lllm_manage_games`
* `lllm_import_csv`
* `lllm_view_logs`
* `upload_files`
* `lllm_manage_media_library`

### UI visibility rules (Manager)

Managers see the plugin top-level menu: **League Manager** with the plugin pages.
Managers are intended to work primarily in League Manager screens.

### Access behavior (implemented)

* Admin bar: show `⚾ League Manager` link for logged-in users with `lllm_manage_seasons`.
* Login redirect: users with role `lllm_manager` are redirected to `admin.php?page=lllm-welcome`.
* Media Library scope: managers with `lllm_manage_media_library` can browse sitewide media in both modal and list views.

---

## 3) Data integrity rules (hard rules)

1. **No mixing stats across seasons.**
2. **No mixing stats across divisions.**
3. Games reference **Teams**, not team names.
4. CSV imports must not create new teams implicitly (no “auto-create teams from CSV”).
5. Any CSV error → reject entire import (atomic). No partial writes.

---

## 4) Database schema (custom tables)

> Use InnoDB. Prefer **unique indexes** over foreign keys (WordPress hosting environments vary). Enforce referential integrity in code.

### 4.1 `wp_lllm_seasons`

* `id` (BIGINT PK, auto)
* `name` (VARCHAR 120, not null)
* `slug` (VARCHAR 140, unique, not null)
* `timezone` (VARCHAR 64, not null; default = WP site timezone)
* `is_active` (TINYINT(1), default 0)
* `created_at` (DATETIME UTC)
* `updated_at` (DATETIME UTC)

**Indexes**

* UNIQUE(`slug`)
* INDEX(`is_active`)

### 4.2 `wp_lllm_divisions` (Season-Divisions)

* `id` (BIGINT PK)
* `season_id` (BIGINT, not null)
* `name` (VARCHAR 80, not null)  // “8U”
* `slug` (VARCHAR 160, unique, not null) // e.g., `spring-2026-8u`
* `created_at`, `updated_at`

**Indexes**

* UNIQUE(`slug`)
* INDEX(`season_id`)
* UNIQUE(`season_id`, `name`)  *(prevents duplicate “8U” within same season)*

### 4.3 `wp_lllm_team_masters` (Franchises)

* `id` (BIGINT PK)
* `name` (VARCHAR 120, not null)
* `slug` (VARCHAR 140, unique, not null)
* `team_code` (VARCHAR 60, unique, not null) // franchise code for CSV (e.g. `dirtbags`)
* `logo_attachment_id` (BIGINT null) // WP media ID
* `created_at`, `updated_at`

**Indexes**

* UNIQUE(`team_code`)
* UNIQUE(`slug`)

### 4.4 `wp_lllm_team_instances` (Teams)

* `id` (BIGINT PK)
* `division_id` (BIGINT, not null)
* `team_master_id` (BIGINT, not null)
* `display_name` (VARCHAR 120 null) // internal field; team import defaults to Franchise name
* `created_at`, `updated_at`

**Indexes**

* INDEX(`division_id`)
* INDEX(`team_master_id`)
* UNIQUE(`division_id`, `team_master_id`) *(ensures one instance per division per franchise)*

### 4.5 `wp_lllm_games`

* `id` (BIGINT PK)
* `game_uid` (CHAR(12) unique, not null) // human-friendly stable ID for CSV (e.g., base32)
* `division_id` (BIGINT, not null)
* `competition_type` (VARCHAR 20, not null, default `regular`) // `regular|playoff`
* `playoff_round` (VARCHAR 20, null) // `r1|r2|championship` when playoff
* `playoff_slot` (VARCHAR 20, null) // slot inside round, stringified for UI compatibility
* `source_game_uid_1` (CHAR(12), null) // feeder game UID for winner placeholder/advancement wiring
* `source_game_uid_2` (CHAR(12), null) // second feeder UID (used by championship)
* `home_team_instance_id` (BIGINT, not null)
* `away_team_instance_id` (BIGINT, not null)
* `location` (VARCHAR 160, not null) // free text v1
* `start_datetime_utc` (DATETIME, not null)
* `home_score` (INT null)
* `away_score` (INT null)
* `status` (ENUM-ish via VARCHAR 20, not null) // scheduled|played|canceled|postponed
* `notes` (VARCHAR 255 null)
* `created_at`, `updated_at`

**Indexes**

* UNIQUE(`game_uid`)
* INDEX(`division_id`, `start_datetime_utc`)
* INDEX(`division_id`, `competition_type`, `playoff_round`, `playoff_slot`)
* INDEX(`source_game_uid_1`)
* INDEX(`source_game_uid_2`)
* INDEX(`home_team_instance_id`)
* INDEX(`away_team_instance_id`)
* UNIQUE(`division_id`, `start_datetime_utc`, `home_team_instance_id`, `away_team_instance_id`)
  *(prevents accidental duplicates; relies on datetime precision—acceptable for v1)*

---

## 5) Standings rules (decisive defaults)

### 5.1 Included games

* Only games where `status = played`
* Only within the selected `division_id` (Season-Division)
* Canceled/postponed games never count.

### 5.2 Team stats computed per Team

* GP = games played
* W/L/T

  * If home_score > away_score → home W, away L
  * If away_score > home_score → away W, home L
  * If equal → T for both
* RF/RA: runs for/against
* RD = RF - RA
* Win% = (W + 0.5*T) / GP

  * If GP = 0 → Win% = 0.000

### 5.3 Sorting order (tie-breakers)

1. Win% desc
2. Wins desc
3. Run Differential desc
4. Runs Against asc
5. Team Name asc

### 5.4 Edge cases

* **Ties are supported** (baseball time-limits happen; keep it simple).
* **Forfeits:** v1 treat as `played` with scores entered (e.g., 0–1) and optional `notes="Forfeit"`.
* **Mercy rules:** out of scope; standings use recorded score.

### 5.5 Caching

* Cache standings per division (e.g., transient `lllm_standings_{division_id}`).
* Bust cache on any game insert/update/delete affecting that division.

---

## 6) Manager workflows (dad-proof UX)

### 6.1 Admin menu structure

Top-level: **League Manager**

Subpages:

1. Seasons
2. Divisions
3. Franchises
4. Teams (team assignment + cloning)
5. Games (view + quick edit)
6. Import Logs
7. Shortcode Generator

Managers should never need WP’s standard editor screens.

Shortcode Generator safety defaults:
- Dropdown fields remain the default path for slug/code attributes.
- Some dropdowns expose an explicit **Advanced: custom value** toggle for manual overrides.
- The toggle is OFF by default and visually warns that curated options are bypassed.
- Manual values are sanitized to `[A-Za-z0-9_-]` before shortcode preview/copy output.

### 6.2 Shortcode Generator screen

The plugin includes a dedicated **League Manager → Shortcode Generator** submenu page for building shortcodes.

- Capability: `lllm_manage_seasons`
- Page title/menu label: `Shortcode Generator`
- Renders a shortcode-type dropdown, dynamic attribute controls (primarily dropdown-first), generated preview textarea, and copy button.
- Uses page-scoped admin assets only on this screen (`assets/shortcode-generator-admin.js` and `assets/shortcode-generator-admin.css`).
- Uses shared shortcode definitions from `LLLM_Admin::get_shortcode_definition_map()` plus dynamic value sources from plugin data to keep labels, control types, defaults, and attribute order consistent.
- Dynamic dropdown value sources are fetched via `wp_ajax_lllm_shortcode_generator_value_source` and returned as `{label, value}` option arrays.
- Shortcode schema supports dependency metadata (`dependsOn`, `filterBy`) so child attributes can be driven by parent selections (for example `season -> division -> team_code`).
- When a parent field changes, child dynamic selects are refetched with mapped filters, stale child values are cleared if no longer valid, and preview shortcode output updates immediately.
- Client-side option payloads are cached per source+filter key for the admin page session to avoid repeat requests.
- Dynamic selects expose loading, empty, and retryable error states for better resilience.
- Primary user flow is: choose shortcode type → select attributes from dropdowns → copy shortcode.

---

## 7) Setup wizard: required screens and behaviors

### 7.1 Seasons screen

* List seasons + “Active” indicator
* Actions:

  * Create Season
  * Set Active Season (only one active at a time)
  * Archive season (sets inactive, read-only by default)

**Create Season fields**

* Season Name (required)
* Timezone (default WP site TZ)

### 7.2 Divisions screen

* Filter by Season
* Add Division (name: “8U”)

### 7.3 Franchises screen

* Create/edit Franchise:

  * Name (required)
  * Logo upload/select (optional)
  * Franchise Code (auto-generated from slug; editable only by Admin; Managers view-only)
* Franchise Code must be unique and stable (used in CSV forever).

### 7.4 Teams screen (Season/Division Teams)

* Select Season → Division
* Show checklist/grid of Franchises with “Assigned?” toggles
* Buttons:

  * “Assign Selected Franchises"
  * “Remove Selected Franchises” (disabled if teams have games; show warning)

**CSV matrix import (season-wide sync)**

* Download template exports:
  * `franchise_code` column
  * one additional column per division in the selected season
  * one row per franchise code
* Import semantics:
  * any non-empty value = assigned to that season/division
  * empty value OR literal `FALSE` (case-insensitive) = unassigned
* Import applies a full-season sync:
  * creates missing assignments from marked cells
  * removes assignments that are unassigned in the CSV
  * blocks removals when games already exist for that team instance

**Integrity**

* Removing a team instance is blocked if any games exist for it. (Dad-proof: prevent breaking standings.)

---

## 8) CSV Import Wizard (core feature)

### 8.1 Wizard entry: choose scope

Step 1: Select Season → Select Division
Show a big banner: “You are importing games for: **Spring 2026 / 8U**”

### 8.2 Choose import type (two modes)

* **A) Full Schedule Import (Create + Update)**
* **B) Score Update Import (Update only, safest)**

Default recommendation in UI copy:

* “Use **Full Schedule** once at season start.”
* “Use **Score Update** weekly.”

### 8.3 Step 2: Upload CSV

* CSV only (reject XLSX to reduce complexity)
* File size cap (e.g., 2–5 MB)
* Display: “Download Template”

### 8.4 Step 3: Validate (no writes)

Validation must produce:

* Summary:

  * rows read
  * rows valid
  * rows to create
  * rows to update
  * rows unchanged
  * rows invalid
* Preview table (first ~20 changes)
* Downloadable Error Report CSV if any errors

**If any errors → block commit.**

### 8.5 Step 4: Commit (atomic)

* Commit must run inside a DB transaction:

  * If any row fails unexpectedly → rollback
* After commit show:

  * created count
  * updated count
  * unchanged count
  * cache bust result
  * link: “View Games for this Division”

### 8.6 Import logging

Every validation + commit produces a log entry with:

* user_id
* season_id, division_id
* import_type
* original_filename
* totals: rows, created, updated, unchanged, errors
* stored error report CSV (if errors)
* timestamps

---

## 9) CSV specifications (decisive)

### 9.1 Common rules

* Header row required.
* UTF-8.
* Date/time format required: `MM/DD/YYYY` and `HH:MM` (24-hour).
* Status allowed: `scheduled`, `played`, `canceled`, `postponed`
* Scores:

  * Must be blank for scheduled/postponed/canceled
  * Must be non-negative integers for played
* Home team must not equal away team.

### 9.2 Full Schedule Import CSV columns (required)

1. `game_uid` (optional for initial import; included in exports)
2. `start_date` (required)
3. `start_time` (required)
4. `location` (required)
5. `away_team_code` (required)
6. `home_team_code` (required)
7. `status` (optional; defaults to `scheduled`)
8. `away_score` (optional; required if status=played)
9. `home_score` (optional; required if status=played)
10. `notes` (optional)

**Team matching**

* `away_team_code` and `home_team_code` must match Franchises **assigned** to this division.
* The import process resolves `team_code` → `team_master_id` → `team_instance_id` for this division.
* If team code exists but isn’t assigned to the division → error.

**Game identification**

* If `game_uid` provided: update that game only (must belong to this division).
* Else: match on unique key (`division_id`, `start_datetime_utc`, home_team_instance_id, away_team_instance_id).

  * If found: update
  * If not found: create

### 9.3 Score Update CSV columns (required)

1. `game_uid` (required)
2. `away_score` (required)
3. `home_score` (required)
4. `status` (required; typically `played`)
5. `notes` (optional)

**Rules**

* Must only update existing games in this division.
* If `status=played`, scores required.
* If either score is supplied, the row is treated as `played` and both scores must be present.

### 9.4 Export format

The “Download Current Games CSV” export should always include:

* `game_uid`, `start_date(mm/dd/yyyy)`, `start_time(24HR)`, `location`, `away_team_code`, `home_team_code`, `status`, `away_score`, `home_score`, `notes`

This becomes the canonical “edit and re-upload” file.

---

## 10) Validation error messages (must be actionable)

When errors occur, show:

* row number (starting at 2 for first data row)
* column
* provided value
* expected rule
* suggested fix

Examples:

* “Row 12, home_team_code: `Dirt Bags` is not a recognized franchise code for Spring 2026 / 8U. Use one of: `dirtbags`, `pirates`, …”
* “Row 8, status: `final` is invalid. Allowed: scheduled, played, canceled, postponed.”
* “Row 20: status is `played` but home_score is blank.”

Downloadable error report CSV should add an `error` column with the message.

---

## 11) Games admin screen (simple + safe)

### Required features

* Filter: Season → Division
* Table: Date/Time, Location, Away, Home, Status, Score
* Inline quick edit (per row): Status + game type + playoff slot (when playoff) + scores + notes
* “Export CSV” button
* “Go to Import Wizard” button

### Important behaviors

* Editing a played game triggers standings cache bust.
* Prevent invalid state:

  * If status set to played, require scores.

---

## 12) Front-end rendering (shortcodes v1)

### 12.1 Shortcodes

1. Schedule:

   * `[lllm_schedule season="spring-2026" division="8u"]`
   * Defaults: if attributes omitted, use Active Season + first division
   * Optional attributes:

    * `team_code="dirtbags"` (filters to one team within that division by franchise code)
     * `show_past="1"` / `show_future="1"` (default both)
     * `limit="50"`

2. Standings:

   * `[lllm_standings season="spring-2026" division="8u"]`

3. Teams list (optional but useful):

   * `[lllm_teams season="spring-2026" division="8u" show_logos="1"]`

4. Playoff bracket:

   * `[lllm_playoff_bracket season="spring-2026" division="8u"]`
   * Renders generated playoff games for `r1`, `r2`, and `championship` rounds.
   * If feeder results are not final yet, shows winner placeholders until source games are `played`.

### 12.1A Admin shortcode builder (schema source of truth)

* Add a Shortcode Builder to **League Manager → Welcome**.
* Maintain a single shortcode definition map keyed by shortcode name.
* Each entry includes:

  * display label
  * ordered attribute list
  * per-attribute metadata: `label`, `control_type`, `value_source`, `default_value`, `optional`, plus dependency metadata (`dependsOn`, `filterBy`) for parent/child attribute chains

Builder requirements:

* Populate shortcode-type dropdown from this map.
* Listen for shortcode-type changes.
* On type change, clear previous attribute UI and previous attribute state.
* Render only attribute controls declared for the selected shortcode type by mapping `control_type` to UI component (`select`, `text`, `number`, `checkbox`).
* Resolve select options from `value_source` (`static` options in schema or `dynamic` source keys from plugin data), apply dependency filters from `filterBy`, then initialize each rendered field from `default_value`.
* On parent field changes, recompute dependent child options in schema order, clear invalid child selections, and re-render shortcode preview immediately.
* Treat every select option as `{ label, value }`: render menu text from `label` (optionally showing `value` for clarity), but store both in state.
* Recompute shortcode preview output on every field change.
* Render preview in a readonly field at the bottom of the builder.
* Add a **Copy Shortcode** action that is idempotent and does not reload the page.
* Copy behavior: try `navigator.clipboard.writeText(previewValue)`, show success notice on resolve, and on rejection/select-failure select preview text and show `Press Ctrl/Cmd+C` fallback guidance.
* Preview and copy must always serialize shortcode attribute `value` (never `label`) to preserve shortcode compatibility.
* Build final shortcode string from this map in the exact configured attribute order.
* Omit optional attributes when their values are empty.


#### Maintaining shortcode schema + dropdown options

Implementation source of truth lives in `LLLM_Admin::get_shortcode_definition_map()` and `LLLM_Admin::get_shortcode_generator_value_source_options()`.

When adding or maintaining dropdown options:

1. In the shortcode definition map, add/update the attribute in both:
   - `attributes` ordered list (controls output order), and
   - `attribute_meta` entry.
2. For dropdowns, set:
   - `control_type => select`
   - `value_source => { type: static|dynamic, ... }`
   - `optional`, `default_value`, and optional `allow_custom_value`.
3. For static dropdowns, maintain curated options inline under `value_source.options` as `{label, value}` pairs.
4. For dependent dropdowns, wire dependency metadata:
   - `dependsOn` for parent attribute names.
   - `filterBy` mapping from parent attribute to provider filter key.

#### Wiring dynamic option providers

Dynamic providers are keyed by `value_source.key` and served via the AJAX route `wp_ajax_lllm_shortcode_generator_value_source`.

- Add/maintain provider logic in `get_shortcode_generator_value_source_options($source_key, $filters)`.
- Normalize/validate incoming filter keys with `sanitize_key` and values with `sanitize_text_field`.
- Return options as `{ label, value }` arrays only.
- Keep provider keys aligned across:
  - schema `value_source.key`,
  - AJAX request `source_key`,
  - PHP provider switch branches.

Current provider keys:
- `season_slugs`
- `division_slugs` (supports `season_slug` filter)
- `team_codes` (supports `season_slug` and `division_slug` filters)

#### Troubleshooting notes (generator)

Empty dropdowns / no options:
- Verify backing records exist in DB/admin screens (for example, no divisions exist yet for selected season).
- Verify dependency order: parent value must be selected before child options can resolve.
- Use inline **Retry** when fetch errors occur; if still empty, reload the admin page to reset in-memory option cache.

Custom-value override behavior:
- If `allow_custom_value` is enabled for an attribute, custom mode overrides curated dropdown selection.
- Override is intentional and should be treated as advanced/manual path.
- Sanitization strips everything outside `[A-Za-z0-9_-]` before shortcode serialization.

### 12.2 Display rules (dad-proof)

* Scheduled games show: date/time + location (no scores)
* Played games show: score prominently
* Canceled/Postponed show a badge and no scores
* Include “Last updated” timestamp for schedules/standings

---

## 13) Realistic edge cases (supported behavior)

### 13.1 Postponed and rescheduled games

* Postponed: set status `postponed`, keep original datetime (or update later).
* Reschedule: update `start_date`/`start_time` and set status `scheduled`.

### 13.2 Doubleheaders / repeat matchups

Supported naturally. Two games between same teams are allowed as long as datetime differs.

### 13.3 0–0

Allowed only if status is `played` and league truly uses 0–0 finals (rare). Validation should allow it (don’t special-case).

### 13.4 Ties

Supported automatically (scores equal in a played game).

### 13.5 Franchise renames mid-season

* Rename Franchise name/logo is allowed; franchise code (`team_code`) remains stable.
* If a team truly becomes a new identity, Admin should create a new Franchise + new code.

### 13.6 Playoff business rules (fixed 6-team bracket)

* Format is fixed at **6 teams** in standings order (seeds 1–6).
* Bracket generator creates exactly 5 games in this sequence:

  * `r1` slot `1`: seed 3 vs seed 6
  * `r1` slot `2`: seed 4 vs seed 5
  * `r2` slot `1`: seed 1 vs winner of `r1` slot `2`
  * `r2` slot `2`: seed 2 vs winner of `r1` slot `1`
  * `championship` slot `1`: winner of `r2` slot `1` vs winner of `r2` slot `2`

#### Round + slot naming

* Round codes are stored as `r1`, `r2`, and `championship`.
* Slot values are stored as string numerals (`1`, `2`, etc.) for consistent quick-edit and rendering behavior.
* Display ordering is by round (`r1` → `r2` → `championship`) and then numeric slot order.

#### Feeder linkage rules

* `source_game_uid_1` and `source_game_uid_2` are only valid for playoff games.
* R1 games do not have feeder links.
* R2 games use `source_game_uid_1` to identify the R1 winner feeding that slot.
* Championship uses both feeder fields to reference both R2 winners.
* If feeder game status is unresolved (not `played`), displays should show a placeholder label (`Winner of Game <round>-<slot>`) instead of a resolved team winner.

### 13.7 Manager playoff workflow notes

1. Go to **Games**, pick Season + Division, and verify standings are final.
2. Click **Generate Playoff Bracket** (requires at least 6 teams in standings).
3. Review generated games (`r1`, `r2`, `championship`) and optionally adjust date/time/location.
4. Enter results in Quick Edit as games complete.
5. As feeder games are marked `played`, downstream bracket placeholders resolve automatically to winners.
6. If setup needs to be restarted, use **Reset Playoff Games** and generate again.

---

## 14) Security + reliability requirements

* All write actions require:

  * capability checks
  * nonce verification
* CSV uploads:

  * validate MIME + extension `.csv`
  * server-side file handling (no executing)
* Import commit uses transactions.
* Every import generates a log entry (even failed validation).
* Provide a “dry run validation” always (default step).

---

## 15) Plugin lifecycle (activation, upgrades, uninstall)

### Activation

* Create tables via `dbDelta`.
* Create “Managers” role and assign capabilities.
* Set plugin version in options.

### Upgrades

* Versioned migrations:

  * if schema changes, run incremental SQL migrations
* Runtime schema safety check:

  * on `plugins_loaded`, if required `wp_lllm_games` columns are missing (for example `competition_type`), rerun migrations even when plugin version option appears current
* Preserve data.

### Uninstall

* **Do not delete data by default** (stability).
* Provide an admin-only “Delete all plugin data” tool behind a confirmation gate (optional).

---

## 16) Acceptance criteria checklist

1. Admin can create Season, set it active.
2. Admin/Manager can create Divisions under a Season.
3. Admin/Manager can create Franchises with logos; system generates unique stable franchise codes (`team_code`).
4. Admin/Manager can assign franchises to divisions; can clone assignments from another division.
5. Import Wizard:

   * can validate and reject bad CSVs with row-level errors
   * can commit a valid CSV atomically
6. Export:

   * can export current games CSV including game_uid
7. Score update import:

   * updates scores reliably using game_uid (no name mismatches)
8. Standings:

   * computed correctly per division, only played games
   * tie handling works
   * sorting follows spec
9. Shortcodes render schedules and standings for any season/division.

---

## 17) Suggested implementation notes (for the developer)

* Generate `game_uid` as short stable code (e.g., Crockford Base32) at create time.
* Store datetimes in UTC; convert from season timezone on import and convert back to season timezone for display and current-games CSV export.
* Keep import parsers strict and predictable; no “best guess” parsing.
* Keep all Manager screens custom (no WP post editor confusion).
* Provide “Download Template” and “Download Current CSV” everywhere importing happens.

---

Below are **wireframe-style screen specs** (what’s on each page, button labels, empty states, and key behaviors), plus **CSV templates + realistic example files** (good and bad) including what the **error report** should look like.

---

# A) Admin UI Wireframes (Dad-proof)

## Global: League Manager menu

Top-level: **League Manager**

Subpages (left nav):

1. Seasons
2. Divisions
3. Teams
4. Teams
5. Games
6. Import Wizard
7. Import Logs

**Global UI conventions**

* Every page shows a “Context Bar” at top when Season/Division applies:

  * **Season:** Spring 2026 | **Division:** 8U (Change)
* Use big primary buttons and minimal options.
* Always show “Download Template” and “Download Current CSV” near imports.
* No hidden “advanced” settings in v1.

---

## 1) Seasons Screen

### 1.1 List view

**Title:** Seasons
**Primary button:** + Create Season

**Table columns**

* Name
* Timezone
* Status (Active / Inactive / Archived)
* Actions: Edit | Set Active | Archive

**Empty state**

* “No seasons yet.”
* Button: “Create your first Season”

### 1.2 Create / Edit Season

**Fields**

* Season Name (required) — placeholder: “Spring 2026”
* Timezone (dropdown) — default: site timezone (America/Chicago)
* Active toggle (only one season can be active)

**Buttons**

* Save Season (primary)
* Cancel

**Validation**

* Name required
* Slug uniqueness handled automatically (append -2, -3…)

---

## 2) Divisions Screen

### 2.1 List view

**Title:** Divisions
**Season selector:** (dropdown) “Spring 2026”
**Primary button:** + Add Division

**Table columns**

* Division Name (e.g., 8U)
* Teams Assigned (#)
* Actions: Edit | Manage Teams | Import Games | View Games

**Empty state**

* “No divisions in Spring 2026 yet.”
* Button: “Add a Division”

### 2.2 Add / Edit Division

**Fields**

* Division Name (required) — placeholder “8U”

**Buttons**

* Save Division
* Cancel

**Validation**

* Prevent duplicate division names within the same season (8U already exists)

---

## 3) Franchises Screen

### 3.1 List view

**Title:** Franchises
**Primary button:** + Add Franchise

**Table columns**

* Logo (thumb)
* Franchise Name
* Franchise Code (read-only for Managers)
* Actions: Edit | Change Logo

**Empty state**

* “No franchises yet. Add your franchises once, then assign them to divisions.”
* Button: “Add a Franchise”

### 3.2 Add / Edit Franchise

**Fields**

* Franchise Name (required) — “Dirtbags”
* Franchise Code (auto-generated from name)

  * **Manager view:** read-only
  * **Admin view:** editable with warning: “Changing franchise code can break CSV imports.”
* Logo (Media picker)

**Buttons**

* Save Franchise
* Cancel

**Validation**

* Franchise Code must be unique
* Franchise Name required

---

## 4) Teams Screen (Create Teams)

### 4.1 Main screen

**Title:** Teams
**Selectors**

* Season dropdown
* Division dropdown

**Section: Team Assignment**

* A searchable list/grid of Franchises with:

  * Checkbox “Assigned”
  * Logo + Name + Franchise Code

**Buttons**

* Assign Selected (primary)
* Remove Selected (danger)
* Clone from another Division… (secondary)

**Clone modal**

* “Clone assignments from: [Division dropdown]”
* Button: Clone

**Rules / Warnings**

* Removing a team from a division is blocked if any games exist for that Team.

  * Message: “Can’t remove ‘Dirtbags’ from 8U because games exist. Remove games first.”

**Empty state**

* “No teams exist yet.”
* Button: “Add Franchises” (links to Franchises screen)

---

## 5) Games Screen

### 5.1 List view

**Title:** Games
**Selectors**

* Season dropdown
* Division dropdown

**Buttons**

* Export Current Games CSV (secondary)
* Import Games (primary) → goes to Import Wizard
* Quick Add Game (optional v1; recommended OFF for simplicity)

**Table columns**

* Date/Time (local)
* Location
* Home
* Away
* Status (badge)
* Score (if played)
* Actions: Edit Score/Status | View

**Quick Edit modal (Dad-proof)**

* Status dropdown
* Away Score / Home Score (enabled only if status=played)
* Notes (optional)
* Save

**Validation**

* If status=played, scores required and non-negative integers

---

## 6) Import Wizard (Most important screen)

### Step 1: Choose Context

**Title:** Import Wizard
**Fields**

* Season dropdown
* Division dropdown

**Import type cards**

* **Full Schedule Import** (Create + Update)
  “Use this once at the start of the season or when scheduling changes.”
* **Score Update Import** (Update only)
  “Use this weekly. Safest option.”

Button: Continue

---

### Step 2: Upload CSV

**Title:** Upload CSV
**Buttons**

* Download Template (secondary)
* Choose File (CSV)
* Validate (primary)

**Help text**

* “CSV must be UTF-8, with headers, and date/time format `MM/DD/YYYY` and `HH:MM` (24-hour).”

---

### Step 3: Validation Results (No writes)

**Title:** Review Changes
**Summary panel**

* Rows read
* Creates
* Updates
* Unchanged
* Errors

**If errors**

* Big red banner: “Fix errors before importing.”
* Button: Download Error Report CSV
* Button: Try Again

**If no errors**

* Preview table (first 20 rows of creates/updates)
* Button: Import Now (primary)
* Button: Cancel

---

### Step 4: Post-Commit Behavior

After commit, users are redirected back to Games with an import-complete notice.

---

## 7) Import Logs Screen

**Title:** Import Logs
**Table columns**

* Date/Time
* Manager
* Season / Division
* Import Type
* File Name
* Result (Success / Failed)
* Actions: View Details | Download Error Report (if failed)

**Log Detail view**

* Full counts + error snippet
* Link to affected division games

---

# B) CSV Templates + Examples

Assume Season **Spring 2026**, Division **8U**, timezone **America/Chicago**.

## Reference teams (Franchises)

These are created once in Franchises screen:

| Franchise Name | Franchise Code |
| -------------- | -------------- |
| Dirtbags  | dirtbags  |
| Pirates   | pirates   |
| Cubs      | cubs      |
| A’s       | as        |

> Managers should never type team names in CSV. Only use franchise codes in the **team_code** columns.

---

## 0) Teams Assignment Matrix — Template (season-wide)

Example for season divisions `7U Minor`, `7U Major`, `8U Minor`, `8U Major`:

```csv
franchise_code,7U Minor,7U Major,8U Minor,8U Major
hawks,,,,
lions,,,,
bears,,,,
```

Interpretation:
- non-empty cell (e.g. `1`, `yes`, `active`) means assigned
- empty cell and `FALSE` mean unassigned

---

## 1) Full Schedule Import — Template (blank)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
```

### 1A) Full Schedule Import — Example (initial season upload; no game_uid)

All games are scheduled; scores blank.

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
,03/14/2026,18:45,Field 2,cubs,as,scheduled,,,
,03/21/2026,17:30,Field 1,pirates,cubs,scheduled,,,
,03/21/2026,18:45,Field 2,as,dirtbags,scheduled,,,
```

**Expected validation result**

* Creates: 4
* Updates: 0
* Errors: 0

---

## 2) “Download Current Games CSV” — Example export after import

After commit, system exports with `game_uid` filled in (example UIDs).

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
G8K4Q2M9T1A3,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
K2P7N6D4R9B1,03/14/2026,18:45,Field 2,cubs,as,scheduled,,,
M5T1C8H3W2Z7,03/21/2026,17:30,Field 1,pirates,cubs,scheduled,,,
R9B1L6S2J4Q8,03/21/2026,18:45,Field 2,as,dirtbags,scheduled,,,
```

> This export becomes the canonical “edit and re-upload” file.

---

## 3) Score Update Import — Template (blank)

```csv
game_uid,away_score,home_score,status,notes
```

### 3A) Score Update Import — Example (weekly)

```csv
game_uid,away_score,home_score,status,notes
G8K4Q2M9T1A3,6,4,played,
K2P7N6D4R9B1,3,3,played,Tie game (time limit)
```

**Expected result**

* Updates: 2
* Standings cache bust: yes

---

## 4) Full Schedule Import — Example (reschedule + score update in one file)

This is allowed in Full Schedule mode because it can create/update.

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
G8K4Q2M9T1A3,03/14/2026,17:30,Field 1,dirtbags,pirates,played,6,4,
K2P7N6D4R9B1,03/14/2026,18:45,Field 2,cubs,as,played,3,3,Tie game (time limit)
M5T1C8H3W2Z7,03/22/2026,14:00,Field 3,pirates,cubs,postponed,,,Rainout
R9B1L6S2J4Q8,03/21/2026,18:45,Field 2,as,dirtbags,scheduled,,,
```

**Expected result**

* Updates: 3 (two played, one postponed + date/location change)
* Unchanged: 1

---

# C) “Bad CSV” examples + what the error report should show

## Bad Example 1: team code typo (Dad-proof rejection)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,Dirt Bags,pirates,scheduled,,,
```

**Why it fails**

* `home_team_code` must match a known assigned franchise code. `Dirt Bags` is not valid.

**UI error**

* “Row 2, home_team_code: `Dirt Bags` is not a valid franchise code for Spring 2026 / 8U. Use: dirtbags, pirates, cubs, as.”

**Downloadable Error Report CSV**

```csv
row_number,game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes,error
2,,03/14/2026,17:30,Field 1,Dirt Bags,pirates,scheduled,,,,Invalid home_team_code 'Dirt Bags'. Must be one of: dirtbags|pirates|cubs|as
```

---

## Bad Example 2: played game missing scores

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,played,,,
```

Error:

* “Row 2: status is played but home_score/away_score are blank.”

---

## Bad Example 3: scheduled game includes scores (prevents accidental ‘0-0 played’ confusion)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,0,0,
```

Error:

* “Row 2: status is scheduled; scores must be blank.”

---

## Bad Example 4: invalid datetime format

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,5:30 PM,Field 1,dirtbags,pirates,scheduled,,,
```

Error:

* “Row 2, start_date/start_time: must be `MM/DD/YYYY` and `HH:MM` (24-hour). Example: 03/14/2026 17:30”

---

## Bad Example 5: home and away are the same team

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,dirtbags,scheduled,,,
```

Error:

* “Row 2: home team cannot equal away team.”

---

## Bad Example 6: Score Update import with unknown game_uid

```csv
game_uid,away_score,home_score,status,notes
NOTAREALUID,6,4,played,
```

Error:

* “Row 2: game_uid `NOTAREALUID` not found in Spring 2026 / 8U. Export current games CSV to get valid IDs.”

---

## Bad Example 7: duplicate games (same datetime + same teams)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
```

Error:

* “Row 3 duplicates Row 2: same start_date/start_time + same teams.”

---

# D) Suggested “Download Template” files Managers will actually use

To reduce mistakes, provide template downloads in the wizard:

1. **Download Full Schedule Template** (blank header only)
2. **Download Score Update Template** (blank header only)
This keeps the Manager workflow simple:

* Download template → fill/update rows → upload CSV

---

# Sample Seed (for dev + stakeholder demo)

Below is a realistic “seed” dataset that an Admin can enter in under 10 minutes to demonstrate the whole workflow.

### 1) Create Season

* **Season Name:** Spring 2026
* **Timezone:** America/Chicago
* **Set Active:** Yes

### 2) Create Divisions (in Spring 2026)

* 8U
* 9U

### 3) Create Franchises (name + code + logo optional)

(These codes are what appear in CSVs)

| Franchise Name | Franchise Code | Logo     |
| -------------- | -------------- | -------- |
| Dirtbags  | dirtbags  | optional |
| Pirates   | pirates   | optional |
| Cubs      | cubs      | optional |
| A’s       | as        | optional |
| Hawks     | hawks     | optional |
| Giants    | giants    | optional |

### 4) Assign franchises to each division (creates Teams)

**Spring 2026 / 8U**

* dirtbags
* pirates
* cubs
* as

**Spring 2026 / 9U**

* dirtbags
* hawks
* giants
* cubs

> Note: Dirtbags + Cubs appear in both divisions, but will be treated as totally separate stats entities per division.

---

## 5) Initial schedule import CSVs (Full Schedule Import)

### 5A) Spring 2026 / 8U — Full Schedule Import (initial)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
,03/14/2026,18:45,Field 2,cubs,as,scheduled,,,
,03/21/2026,17:30,Field 1,pirates,cubs,scheduled,,,
,03/21/2026,18:45,Field 2,as,dirtbags,scheduled,,,
,03/28/2026,17:30,Field 1,dirtbags,cubs,scheduled,,,
,03/28/2026,18:45,Field 2,pirates,as,scheduled,,,
```

### 5B) Spring 2026 / 9U — Full Schedule Import (initial)

```csv
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/15/2026,17:30,Field 1,dirtbags,hawks,scheduled,,,
,03/15/2026,18:45,Field 2,giants,cubs,scheduled,,,
,03/22/2026,17:30,Field 1,hawks,giants,scheduled,,,
,03/22/2026,18:45,Field 2,cubs,dirtbags,scheduled,,,
,03/29/2026,17:30,Field 1,dirtbags,giants,scheduled,,,
,03/29/2026,18:45,Field 2,hawks,cubs,scheduled,,,
```

After import, the system generates `game_uid` values and the Manager can export “Current Games CSV” which includes those IDs.

---

## 6) Weekly score update CSV example (Score Update Import)

Assume the system exported these example IDs (they’ll be different in reality):

### 6A) Spring 2026 / 8U — Score Update Import

```csv
game_uid,away_score,home_score,status,notes
G8K4Q2M9T1A3,6,4,played,
K2P7N6D4R9B1,3,3,played,Tie game (time limit)
```

### 6B) Spring 2026 / 9U — Score Update Import

```csv
game_uid,away_score,home_score,status,notes
H1D7P3K9M2Q4,2,5,played,
W8Z2C6R1T5N7,7,1,played,
```

**Expected demo outcome**

* 8U standings show Dirtbags with a win, Cubs and A’s with a tie game reflected, etc.
* 9U standings show different Dirtbags stats than 8U (proving no cross-division mixing).

---

If you want the seed to be even more demo-friendly, I can also provide a “scripted demo path” (exact clicks in order) that a stakeholder can follow to see: create season → assign teams → import schedule → import scores → view standings and schedule pages.
