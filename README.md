# Little League League Manager (LLLM)

LLLM is a WordPress plugin that helps Little League volunteers manage seasons, divisions, teams, schedules, and standings with a simple CSV-driven workflow.

## What it does

- Create seasons and divisions
- Maintain franchises with stable franchise codes
- Assign franchises to divisions (Teams)
- Import schedules and weekly score updates via CSV
- View games in the admin with quick edit
- Render schedules and standings via shortcodes
- Build shortcodes in wp-admin via a schema-driven Shortcode Builder

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
5. **Import Wizard** → Upload the schedule CSV.

## Games quick edit options

On the **Games** screen, each row includes Quick Edit controls for game metadata and result entry.

### Game Type options

- `Regular Game` → regular season game (`competition_type=regular`)
- `Playoff R1` → playoff round 1 (`competition_type=playoff`, `playoff_round=r1`)
- `Playoff R2` → playoff round 2 (`competition_type=playoff`, `playoff_round=r2`)
- `Championship` → final game (`competition_type=playoff`, `playoff_round=championship`)

For playoff types, Quick Edit also exposes the round slot (`playoff_slot`) used for bracket ordering.

## CSV imports

There are two import modes:

### Full Schedule Import
Use at season start or when the schedule changes.

Required headers:

```
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
```

Example:

```
game_uid,start_date(mm/dd/yyyy),start_time(24HR),location,away_team_code,home_team_code,status,away_score,home_score,notes
,03/14/2026,17:30,Field 1,dirtbags,pirates,scheduled,,,
,03/21/2026,18:45,Field 2,cubs,as,scheduled,,,
```

### Score Update Import
Use weekly updates with existing game IDs.

Required headers:

```
game_uid,away_score,home_score,status,notes
```

Example:

```
game_uid,away_score,home_score,status,notes
G8K4Q2M9T1A3,6,4,played,
K2P7N6D4R9B1,3,3,played,Tie game
```

## CSV templates for Divisions, Franchises, and Teams

The **Divisions** and **Teams** admin screens include CSV helpers for template download and import. Divisions include a separate CSV validation step; Teams currently import directly from template format.

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

## Admin shortcode builder

On **League Manager → Welcome**, use the built-in **Shortcode Builder** to generate valid shortcode strings.

- Choose a shortcode type from a dropdown populated from a shortcode definition map.
- Switching shortcode types clears previously rendered attribute controls and prior attribute state.
- Attribute fields render dynamically for only the selected shortcode type and initialize from schema default values.
- Preview output recomputes on every attribute field change.
- Generated shortcode output follows a consistent attribute order based on the shared schema and omits empty optional attributes.

This keeps shortcode labels, supported attributes, defaults, and output formatting aligned in one place.

## Shortcodes

### Schedule

```
[lllm_schedule season="spring-2026" division="8u" show_past="1" show_future="1" limit="50"]
```

Optional filter by franchise code (`team_code`):

```
[lllm_schedule season="spring-2026" division="8u" team_code="dirtbags"]
```

### Standings

```
[lllm_standings season="spring-2026" division="8u"]
```

### Teams list

```
[lllm_teams season="spring-2026" division="8u" show_logos="1"]
```

### Playoff bracket

```
[lllm_playoff_bracket season="spring-2026" division="8u"]
```

This shortcode renders the generated 6-team playoff bracket for the selected season/division.

## Playoff Bracket (6-team)

The built-in bracket generator uses a fixed, single-elimination 6-team format based on standings order (seeds 1–6):

- **R1 Game 1:** Seed 3 vs Seed 6
- **R1 Game 2:** Seed 4 vs Seed 5
- **R2 Game 1:** Seed 1 vs winner of R1 Game 2
- **R2 Game 2:** Seed 2 vs winner of R1 Game 1
- **Championship:** winner of R2 Game 1 vs winner of R2 Game 2

### Unresolved feeder behavior

When a downstream playoff game references a feeder game that has not been played yet, bracket display shows a placeholder in the form `Winner of Game <round>-<slot>` until that feeder game status is `played`.

## Documentation

Full product and technical specs live in `documentation.md`.

## Troubleshooting

### Import fails with: `Unknown column 'competition_type' in 'INSERT INTO'`

This usually means plugin code was updated but the database schema migration has not run yet.

- Visit any wp-admin page while the plugin is active; LLLM now performs a runtime schema check and triggers migrations when required game columns are missing.
- If needed, deactivate and reactivate the plugin once to force activation migrations.
- Retry the import after the migration completes.
