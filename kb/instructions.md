# Little League Manager Plugin: Step-by-Step Guide

This guide is for league volunteers and managers who need a simple, reliable process to run the season.

---

## What this plugin does

The **League Manager** plugin helps you:

- Set up seasons and divisions.
- Create franchises (team identities + franchise codes).
- Assign franchises to divisions as active teams.
- Import schedules and weekly score updates with CSV files.
- Review games, update results, and publish schedule/standings/teams on your website.

Think of the setup in this order:

1. **Seasons**
2. **Divisions**
3. **Franchises**
4. **Teams**
5. **Games (Import Wizard + Quick Edit)**
6. **Import Logs**
7. **Shortcode Generator**

---

## Before you begin

- Use a desktop/laptop (CSV work is easier).
- Keep franchise codes simple and consistent (for example: `dirtbags`, `pirates`, `red-sox`).
- Do all CSV prep in **Google Sheets**, then download as CSV.
- Do not rename required CSV headers.

---

## Menu guide: what each submenu does

### Welcome
A quick summary page with the recommended workflow and reminders.

### Seasons
Create and manage seasons (example: **Spring 2026**).

- Add season name.
- Set timezone.
- Mark one season as active (recommended).

### Divisions
Create divisions inside a season (example: **8U**, **10U**).

- Add manually, or
- Import a list from CSV using the provided template.

### Franchises
Create franchise records (team identity).

- Franchise name (display name).
- Franchise code (used in CSV imports).
- Optional logo.

### Teams
Assign franchises to divisions for a selected season.

- A team assignment means “this franchise plays in this division this season.”
- Includes CSV import helpers for bulk assignment.

### Games
Your schedule + results workspace.

- Filter by season/division.
- Use **Add New Game** for one-off manual game creation in the current season/division context.
- Run the **Import Wizard** for full schedules and score updates.
- Use **Export Current Games CSV** to pull current data.
- Use **Quick Edit** for one-off corrections.
- Use **Add Game Manually** for single game entry without CSV.
- Use game **Type** (`regular` or `playoff`) when creating/editing games.
- Playoff games are managed manually using the same Add Game, Quick Edit, and CSV workflows as regular games.

### Import Logs
Audit trail of CSV imports.

- See success/failure history.
- Download error report CSV for failed imports.

### Shortcode Generator
Build and copy shortcodes for schedule/standings/team list display.

- Select shortcode type.
- Fill attributes.
- Copy and paste into a page/post.

---

## Google Sheets workflow for all CSV imports

Use this process every time (important for clean imports):

1. Download the correct template from the plugin page.
2. Open Google Sheets → **File → Import** → Upload template.
3. Fill only the expected columns.
4. Keep header row exactly as provided.
5. Remove extra blank rows at bottom.
6. Confirm dates/times are in required format where needed.
7. **File → Download → Comma Separated Values (.csv)**.
8. Upload that CSV in the plugin.

### Google Sheets formatting tips

- **Franchise codes:** lowercase letters/numbers/hyphens is safest.
- **Dates:** use `MM/DD/YYYY`.
- **Times:** use `HH:MM` in 24-hour format (for example `17:30`).
- Avoid merged cells, formulas that output errors, or extra tabs/sheets.
- If Sheets auto-formats values unexpectedly, set column format to **Plain text** before pasting.

---

## Step 1: Seasons

1. Go to **League Manager → Seasons**.
2. Click **Add Season**.
3. Enter season name (example: `Spring 2026`).
4. Confirm timezone.
5. Optionally set as active season.
6. Save.

**Why this matters:** imports use season/division context and timezone for schedule dates.

---

## Step 2: Divisions (manual or CSV)

### Manual method

1. Go to **League Manager → Divisions**.
2. Choose the season.
3. Add divisions one by one.

### CSV method (Google Sheets)

1. In **Divisions**, click **Download Template**.
2. Open template in Google Sheets.
3. Fill `division_name` rows (one per division).
4. Download as CSV.
5. Upload with **Import CSV**.

**Tip:** Keep division names exactly how you want them shown on the website.

---

## Step 3: Franchises (manual or CSV)

### Manual method

1. Go to **League Manager → Franchises**.
2. Add each franchise name and code.
3. Optionally attach logo.

### CSV method (Google Sheets)

1. In **Franchises**, click **Download Template**.
2. Open in Google Sheets.
3. Fill:
   - `franchise_name`
   - `franchise_code`
4. Keep every `franchise_code` unique.
5. Download as CSV.
6. (Recommended) Run **Validate CSV**.
7. Run **Import CSV**.

**Why codes matter:** game imports use team/franchise codes, so consistent codes prevent mismatches.

---

## Step 4: Teams (assign franchises to divisions)

This step connects franchises to divisions for a season.

1. Go to **League Manager → Teams**.
2. Select the season.
3. Use manual assignment or CSV import.

### CSV assignment process (Google Sheets)

1. Click **Download Template** (season-specific matrix).
2. Optional: click **Download Franchise Codes** for a clean list to copy from.
3. Open template in Google Sheets.
4. In each division column:
   - Any non-empty value = assigned.
   - Blank or `FALSE` = unassigned.
5. Download as CSV.
6. Upload via **Import CSV**.

**Important:** This import is a season-wide assignment sync. Review carefully before importing.

---

## Step 4.5: Add one game manually

Use this when you only need to add a single game and do not want to run a CSV import.

1. Go to **League Manager → Games** and select season + division.
2. In **Add Game**, enter date, time, location, away/home franchise codes, and status.
3. Optional: add scores and notes.
4. Click **Add Game**.

Validation rules:
- Away and home codes must be different and assigned to that division.
- Date/time uses the season timezone and is stored in UTC.
- Scores are required for `played`, and must be blank for non-`played` statuses.

## Step 5: Games import wizard (full schedule)

Use this at the start of season or when the schedule is rebuilt.

1. Go to **League Manager → Games**.
2. Select season + division.
3. Optional: click **Add New Game** to create a starter game manually.
4. Click **Import Games**.
5. Choose **Full Schedule Import**.
6. Download template.
7. Fill in Google Sheets with required headers:
   - `game_uid`
   - `start_date(mm/dd/yyyy)`
   - `start_time(24HR)`
   - `location`
   - `away_team_code`
   - `home_team_code`
   - `regular_or_playoff`
   - `status`
   - `away_score`
   - `home_score`
   - `notes`
8. Leave `game_uid` blank for new games (system can generate IDs).
9. Download as CSV and upload.
10. Click **Validate CSV**.
11. Review preview/errors.
12. Click **Import Now** when clean.

### Google Sheets checks before upload

- Team codes match franchise codes exactly.
- Date/time format is correct.
- Status values are valid (`scheduled`, `played`, `canceled`, `postponed`).
- If a game is `played`, both scores should be present.

### Manual game entry (no CSV)

1. In **League Manager → Games**, expand **Add Game Manually**.
2. Enter Date + Start Time (5-minute step) using the displayed site timezone hint.
3. Enter location.
4. Select away/home teams (labels include franchise name + code).
5. Select status (`scheduled`, `played`, `canceled`, `postponed`).
6. Enter scores only when status is `played` (score fields stay hidden/disabled otherwise).
7. Optional: add notes, then click **Create Game**.

Guardrails:
- Create button is disabled when away and home are the same team.
- Server-side validation still enforces team/status/score rules.

---

## Step 6: Weekly score updates (Google Sheets + CSV)

Use this after games are played.

1. Go to **League Manager → Games**.
2. Select season + division.
3. Enter Import Wizard and choose **Score Update Import**.
4. Download score update template.
5. Fill in Google Sheets headers:
   - `game_uid`
   - `away_score`
   - `home_score`
   - `status`
   - `notes`
6. Download as CSV.
7. Upload and validate.
8. Import when preview looks correct.

### Best practice for score updates

- First use **Export Current Games CSV** to get valid `game_uid` values.
- Paste needed rows into your score update sheet.
- Add final scores and notes.
- Re-download CSV and import.

**Behavior note:** if scores are provided, the game is treated as played during validation/import.

**Import commit note:** after a successful import, the plugin redirects back to the same Games season/division filter. If the commit inserted any new games, the existing **Game updated** (`game_saved`) success banner is shown; otherwise it shows the standard import-complete notice.

---

## Step 7: Quick edits and maintenance

In **Games**, use **Quick Edit** for small corrections:

- Status
- Scores
- Date/time
- Location
- Game type (`regular` or `playoff`)

Use CSV import for bulk changes; use Quick Edit for one-off fixes.

---

## Step 8: Use Import Logs to troubleshoot

If an import fails:

1. Open **League Manager → Import Logs**.
2. Find the failed job.
3. Download the error report CSV.
4. Fix the listed rows in Google Sheets.
5. Re-download CSV and re-import.

---

## Step 9: Publish front-end views with shortcodes

1. Go to **League Manager → Shortcode Generator**.
2. Select shortcode type (Schedule, Standings, Teams).
3. For Schedule shortcodes, set `type` to `regular` or `playoff` as needed.
4. Set season/division/team filters.
5. Copy shortcode.
6. Paste into a WordPress page/post.

---

## Recommended operating rhythm

- **Pre-season:** create seasons/divisions/franchises/teams + full schedule import.
- **Weekly:** export games → update score CSV in Google Sheets → score update import.
- **As needed:** Quick Edit for single game corrections.
- **After every import:** check Import Logs for confirmation.

---

## Common mistakes to avoid

- Editing/removing CSV header names.
- Using franchise names instead of franchise codes in code fields.
- Date/time format drift caused by spreadsheet auto-formatting.
- Importing before team assignments are complete.
- Reusing old CSVs without refreshing current `game_uid` values.

---

## Quick checklist

- [ ] Season created and timezone correct.
- [ ] Divisions created.
- [ ] Franchises + codes loaded.
- [ ] Teams assigned to divisions.
- [ ] Full schedule imported and validated.
- [ ] Weekly score update process documented for volunteers.
- [ ] Front-end shortcodes placed on pages.

## Release regression checklist (manual)

- [ ] Create one regular and one playoff game manually.
- [ ] Import CSV rows for both regular and playoff games.
- [ ] Confirm regular schedule shortcode excludes playoff games.
- [ ] Confirm playoff schedule shortcode shows only playoff games (including legacy playoff metadata rows).
- [ ] Confirm shortcode generator outputs a valid schedule shortcode with a `type` attribute.
