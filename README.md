# Little League League Manager (LLLM)

LLLM is a WordPress plugin that helps Little League volunteers manage seasons, divisions, teams, schedules, and standings with a simple CSV-driven workflow.

## What it does

- Create seasons and divisions
- Maintain team masters with stable team codes
- Assign teams to divisions
- Import schedules and weekly score updates via CSV
- View games in the admin with quick edit
- Render schedules and standings via shortcodes

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
3. **Teams** → Add team masters and verify team codes.
4. **Division Teams** → Assign team masters to a division.
5. **Import Wizard** → Upload the schedule CSV.

## CSV imports

There are two import modes:

### Full Schedule Import
Use at season start or when the schedule changes.

Required headers:

```
game_uid,start_datetime,location,home_team_code,away_team_code,status,home_score,away_score,notes
```

Example:

```
game_uid,start_datetime,location,home_team_code,away_team_code,status,home_score,away_score,notes
,2026-03-14 17:30,Field 1,dirtbags,pirates,scheduled,,,
,2026-03-21 18:45,Field 2,cubs,as,scheduled,,,
```

### Score Update Import
Use weekly updates with existing game IDs.

Required headers:

```
game_uid,home_score,away_score,status,notes
```

Example:

```
game_uid,home_score,away_score,status,notes
G8K4Q2M9T1A3,6,4,played,
K2P7N6D4R9B1,3,3,played,Tie game
```

## CSV templates for Divisions and Teams

The **Divisions**, **Teams**, and **Division Teams** admin screens include CSV helpers that let you download templates and validate your CSV before importing.

### Divisions template

```
division_name
```

### Teams template

```
team_name,team_code
```

### Division Teams template

```
team_code,display_name
```

## Shortcodes

### Schedule

```
[lllm_schedule season="spring-2026" division="8u" show_past="1" show_future="1" limit="50"]
```

Optional filter by team code:

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

## Documentation

Full product and technical specs live in `documentation.md`.
