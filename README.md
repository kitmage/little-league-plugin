# Little League League Manager (LLLM)

LLLM is a WordPress plugin that helps Little League volunteers manage seasons, divisions, teams, schedules, and standings with a simple CSV-driven workflow.

## What it does

- Create seasons and divisions
- Maintain franchises with stable franchise codes
- Assign franchises to divisions (Teams)
- Import schedules and weekly score updates via CSV
- View games in the admin with quick edit and manual game creation
- Render schedules and standings via shortcodes
- Build shortcodes in wp-admin via a schema-driven Shortcode Generator

## Requirements

- WordPress with PHP 7.4+
- A user with Administrator privileges to activate the plugin

## Installation

1. Copy the plugin folder into your `wp-content/plugins/` directory.
2. Activate **Little League League Manager** in the WordPress admin.
3. Confirm the **League Manager** menu appears in the admin sidebar.

## Setup workflow (admin)

1. **Seasons** → Create a season and set it active.
2. **Divisions** → Add divisions to the season.
3. **Franchises** → Add franchise records and verify franchise codes.
4. **Teams** → Assign franchises to a division.
5. **Games** → Use **Add Game Manually** (inside the expandable section) for one-off entries or open **Import Wizard** for CSV bulk updates.

## Manual game entry

On the **Games** screen, admins can now add a single game directly (without CSV) using the **Add Game** form.

- Required fields: `start_date`, `start_time`, `location`, `away_team_code`, `home_team_code`.
- `status` must be one of: `scheduled`, `played`, `canceled`, `postponed`.
- Team codes must already be assigned to the selected division, and away/home cannot match.
- Date/time is parsed in the selected season timezone, then stored in UTC using the same import parser used by CSV imports.
- Scores are required only when `status=played`; otherwise score fields must be blank.
- Manual adds are written through the shared create path, so duplicate natural keys update existing rows consistently with import behavior.

## Games quick edit options

On the **Games** screen, each row includes Quick Edit controls for game metadata and result entry.

### Game Type options

- `Regular` → regular season game (`competition_type=regular`)
- `Playoff` → playoff game (`competition_type=playoff`)

## Games manual-create form

On the **Games** screen, use **Add Game Manually** to create one game without CSV.

- Date (`input type="date"`) and Start Time (`input type="time" step="300"`) are entered in the site timezone shown beside the Date/Time label.
- Away/Home team dropdowns are sourced from teams assigned to the selected division and display readable franchise names with codes.
- Status supports `scheduled`, `played`, `canceled`, and `postponed`.
- Score fields are only enabled when status is `played`.
- Submit is disabled when away and home teams are the same.

## CSV imports

There are two import modes:

### Full Schedule Import
Use at season start or when the schedule changes.

Date/time normalization:
- `start_date` + `start_time` are interpreted in the selected season's timezone.
- `Export Current Games CSV` writes those fields back in that same season timezone.
- This means import/export round trips keep the same local schedule times.

Required headers:

```
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,regular_or_playoff,status,away_score,home_score,notes
```

Example:

```
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,regular_or_playoff,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,regular,scheduled,,,
,03/21/2026,18:45,Field 2,cubs,as,regular,scheduled,,,
```

### Score Update Import
Use weekly updates with existing game IDs.

Required headers:

```
game_uid,away_score,home_score,status,notes
```

Behavior notes:
- `status` must be one of: `scheduled`, `played`, `canceled`, `postponed` when provided.
- If either score column is populated, import validation will automatically set the game to `played` and apply both scores (even when `status` is blank).
- If a row is treated as `played`, both score columns must be provided.
- Full schedule creates store `start_datetime_utc` in UTC, map `home_team_instance_id`/`away_team_instance_id` from division assignments, and map `regular_or_playoff` to `competition_type` (`regular` by default).
- After commit, the Games screen returns to the same season/division filter; imports that insert new games now reuse the existing `game_saved` success notice.

Example:

```
game_uid,away_score,home_score,status,notes
G8K4Q2M9T1A3,6,4,scheduled,Final entered via score update
K2P7N6D4R9B1,3,3,played,Tie game
```

## CSV templates for Divisions, Franchises, and Teams

The **Divisions** and **Teams** admin screens include CSV helpers for template download and import. Divisions currently show template + import actions only (the validate button is hidden); Teams import directly from template format and now include a quick **Download Franchise Codes** export button.

### Divisions template

```
division_name
```

### Franchises template

```
franchise_name,franchise_code
```

### Teams template

The Teams screen now exports a season-specific matrix template with one division column per division in the selected season.

```
franchise_code,7U Minor,7U Major,8U Minor,8U Major
hawks,,,,
lions,,,,
bears,,,,
```

Import behavior:
- Any non-empty value means the franchise is assigned to that division.
- Empty cells and the literal value `FALSE` (case-insensitive) mean unassigned.
- Import syncs the full season matrix (adds missing assignments and removes assignments that are unassigned in the file, unless blocked by existing games).

## Admin shortcode generator

On **League Manager → Shortcode Generator**, use the built-in generator to create valid shortcode strings.

- Follow the generator flow: **choose shortcode type → select attributes from dropdowns → copy shortcode**.
- Choose a shortcode type from a dropdown populated from a shortcode definition map.
- Dynamic dropdowns (season/division/team) now load options from a secured admin AJAX endpoint and normalize records into `{label, value}` option objects.
- Dynamic option responses are cached in browser memory per source+filter combination (for example, divisions filtered by selected season) to avoid repeated requests.
- Attribute schema now supports dependency metadata (`dependsOn`, `filterBy`) so child dropdowns can react to parent values.
- The generator applies schema dependency chains (for example `season -> division -> team_code`), refetches child options on parent changes, clears stale/invalid child selections, and recomputes the preview immediately.
- Switching shortcode types clears previously rendered attribute controls and prior attribute state.
- Attribute fields render dynamically for only the selected shortcode type, map schema `control_type` to UI controls (`select`, `text`, `number`, `checkbox`), and initialize from schema defaults.
- Select options persist both a user-facing `label` and shortcode-safe `value`; dropdown UI renders both so admins can confirm the exact slug/code being inserted.
- Dynamic selects include field-level states: a loading placeholder while fetching, a disabled **No options available** state when empty, and a retry-friendly inline error message when loading fails.
- Select attributes can optionally enable an **Advanced: custom value** toggle (default OFF) that switches from curated dropdown to manual text entry for one-off slug/code use cases.
- Advanced custom values are sanitized client/server-side to letters, numbers, dashes, and underscores so shortcode output keeps the same safe `attribute="value"` format.
- When custom mode is enabled, helper text clearly warns that curated options are being bypassed.
- Preview output recomputes on every attribute field change in a readonly preview area.
- Use **Copy Shortcode** to copy the generated preview without reloading the page.
- If clipboard copy is blocked, the preview text is selected and a keyboard fallback prompt is shown (Ctrl/Cmd+C).
- Generated shortcode preview/copy always use attribute `value` (never `label`) in a consistent schema order and omit empty optional attributes.

### Shortcode generator troubleshooting

- **Dropdown shows “No options available”**:
  - Verify Seasons/Divisions/Teams data exists for your expected filters.
  - Confirm parent dropdown selections (for example season before division, season+division before team code).
  - Click **Retry** on any field showing a load error.
- **Need a value not shown in the dropdown**:
  - Use **Advanced: custom value** to override curated options for one-off slug/code use cases.
  - Custom mode always takes precedence over the selected dropdown value for that attribute.
  - Custom entries are sanitized to letters, numbers, dashes, and underscores before preview/copy output.

This keeps shortcode labels, control mapping, value sources (static options or dynamic plugin data), defaults, and output formatting aligned in one place.

## Shortcodes

### Schedule

```
[lllm_schedule season="spring-2026" division="8u" type="regular" show_past="1" show_future="1" limit="50"]
```

Schedule output columns are rendered in this order: **Away**, **Home**, **Date/Time**, **Location**, **Win**.
In Away/Home cells, team logo + name are wrapped in `<div class="lllm-team">...</div>`.

Optional filter by franchise code (`team_code`):

```
[lllm_schedule season="spring-2026" division="8u" type="regular" team_code="dirtbags"]
```

Show playoff games only:

```
[lllm_schedule season="spring-2026" division="8u" type="playoff" show_past="1" show_future="1"]
```

`type` accepts:
- `regular` (default behavior)
- `playoff`

Legacy compatibility notes:
- Legacy games with playoff metadata (`playoff_round` or `playoff_slot`) are treated as playoff schedule rows even if `competition_type` was not normalized yet.
- Legacy shortcode `[lllm_playoff_bracket]` is soft-deprecated and currently aliases to `[lllm_schedule type="playoff"]` while emitting a deprecation warning for admins.

### Standings

```
[lllm_standings season="spring-2026" division="8u"]
```

### Teams list

```
[lllm_teams season="spring-2026" division="8u" show_logos="1"]
```

## Manual regression checklist (release QA)

- [ ] Create one regular game and one playoff game manually from the Games screen.
- [ ] Import a CSV containing both regular and playoff games.
- [ ] Verify `[lllm_schedule type="regular"]` excludes playoff games.
- [ ] Verify `[lllm_schedule type="playoff"]` shows only playoff games (including legacy rows that only have playoff round/slot metadata).
- [ ] Verify Shortcode Generator outputs a valid schedule shortcode that includes the `type` attribute.

## Documentation

Full product and technical specs live in `documentation.md`.

## Troubleshooting

### Import fails with: `Unknown column 'competition_type' in 'INSERT INTO'`

This usually means plugin code was updated but the database schema migration has not run yet.

- Visit any wp-admin page while the plugin is active; LLLM now performs a runtime schema check and triggers migrations when required game columns are missing.
- If needed, deactivate and reactivate the plugin once to force activation migrations.
- Retry the import after the migration completes.


## Shortcode output markup updates

Recent shortcode rendering updates include:

- Each shortcode now renders an `h2` heading before output, including Season and Division context plus a content label (Schedule, Standings, Teams, or Playoff Bracket).
- Schedule date/time cells now split output into `<span class="day">`, `<span class="date">`, and `<span class="time">`.
- Output tables now assign class names to every `th` and `td` based on the displayed column heading (`date-time`, `location`, `home`, `away`, `status`, `score`, etc.).
- Team mentions in output tables now include team logo markup in the first column of each row.
- Public shortcode CSS now loads from `assets/lllm-shortcodes.css`, which intentionally contains a comment-only class inventory for theme developers.
