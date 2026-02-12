# Little League Plugin Shortcodes Guide

Want to add league data to a page quickly? Use the plugin's built-in shortcodes.

This guide walks through **every shortcode currently registered in the plugin**, what each one shows, and which attributes you can use.

## Quick start

1. Open a WordPress page or post.
2. Add a **Shortcode** block.
3. Paste one shortcode (examples below).
4. Publish/update the page.

If you leave out season/division attributes, the plugin defaults to the **active season** and the **first division** in that season.

---

## Optional: use the admin Shortcode Builder

If you prefer a guided workflow, go to **League Manager → Welcome** and use **Shortcode Builder**:

1. Select the shortcode type.
2. Fill any attributes you want.
3. Copy the generated shortcode string into your page/post.

When you switch shortcode types, the builder clears prior attribute controls/state, renders only fields for the selected type, initializes each field from schema defaults, and refreshes preview output on every field change.

Optional attributes that are left blank are omitted from the generated shortcode output.

The builder uses the same shortcode schema for labels, attributes, defaults, and attribute order, so generated strings stay consistent.

---

## 1) `[lllm_schedule]` — game schedule table

Use this to show upcoming/past games with date/time, location, teams, status, and score (for played games).

### Example

```text
[lllm_schedule season="spring-2026" division="8u" show_past="1" show_future="1" limit="50"]
```

Filter to one team/franchise code:

```text
[lllm_schedule season="spring-2026" division="8u" team_code="dirtbags"]
```

### Attributes

| Attribute | Type | Default | What it does |
|---|---|---|---|
| `season` | slug text | *(empty)* | Season slug (for example: `spring-2026`). |
| `division` | slug text | *(empty)* | Division slug (for example: `8u`). |
| `team_code` | text | *(empty)* | Filters to games where either team matches this franchise/team code. |
| `show_past` | `"1"` or `"0"` | `"1"` | Include past games (`1`) or hide them (`0`). |
| `show_future` | `"1"` or `"0"` | `"1"` | Include future games (`1`) or hide them (`0`). |
| `limit` | number as text | `"50"` | Max number of rows returned. |

### Good to know

- If both `show_past="0"` and `show_future="0"`, you may get no results.
- Schedule output includes a **Last updated** timestamp.

---

## 2) `[lllm_standings]` — standings table

Use this to show team standings for a division.

### Example

```text
[lllm_standings season="spring-2026" division="8u"]
```

### Attributes

| Attribute | Type | Default | What it does |
|---|---|---|---|
| `season` | slug text | *(empty)* | Season slug. |
| `division` | slug text | *(empty)* | Division slug. |

### Good to know

- Shows team stats like GP, W/L/T, RF, RA, RD, and Win%.
- Includes a **Last updated** timestamp.

---

## 3) `[lllm_teams]` — team list

Use this to show all teams in a division.

### Example

```text
[lllm_teams season="spring-2026" division="8u" show_logos="1"]
```

### Attributes

| Attribute | Type | Default | What it does |
|---|---|---|---|
| `season` | slug text | *(empty)* | Season slug. |
| `division` | slug text | *(empty)* | Division slug. |
| `show_logos` | `"1"` or `"0"` | `"0"` | Shows team logos when set to `"1"` (if logos are available). |

---

## 4) `[lllm_playoff_bracket]` — playoff bracket view

Use this to display generated playoff games for a division.

### Example

```text
[lllm_playoff_bracket season="spring-2026" division="8u"]
```

### Attributes

| Attribute | Type | Default | What it does |
|---|---|---|---|
| `season` | slug text | *(empty)* | Season slug. |
| `division` | slug text | *(empty)* | Division slug. |

### Good to know

- Renders rounds: **Round 1**, **Round 2**, and **Championship**.
- If feeder games are not final yet, bracket slots can show placeholders like **Winner of Game R1-2** until source games are marked played.

---

## Troubleshooting checklist

If a shortcode shows as plain text or no data appears:

1. Confirm the plugin is active.
2. Confirm shortcode name is exact (for example, `lllm_schedule`, not `little_league_schedule`).
3. Check quote style in attributes (use straight quotes like `"8u"`).
4. Verify the `season` and `division` slugs exist.
5. Remove optional attributes and test the base shortcode first.

If needed, send your admin the exact shortcode you used and a screenshot of the page editor.
