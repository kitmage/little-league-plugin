# Little League Plugin Styling Reference

This article is a designer-facing reference for the public shortcode output styles.

Use it when building or updating themes so your overrides stay compatible with the plugin’s responsive behaviors.

---

## Where base styles come from

Core public styles are loaded from:

- `assets/lllm-shortcodes.css`

The plugin outputs semantic class names in shortcode markup (`schedule`, `standings`, `teams`) so theme CSS can target specific components safely.

---

## Responsive behavior overview

### 1) Horizontal overflow wrapper

All major tables are expected inside:

- `.lllm-table-wrap`

This wrapper:

- uses `width: 100%`
- enables horizontal scroll with `overflow-x: auto`
- enables smoother mobile scrolling with `-webkit-overflow-scrolling: touch`

Design implication: keep custom table widths inside this wrapper so narrow devices can still scroll when needed.

### 2) Desktop/tablet table layout (default)

By default, schedule and standings render as standard tables:

- `.lllm-schedule`
- `.lllm-standings`

Base behavior includes:

- collapsed borders
- compact font sizing
- consistent cell padding
- alternating row shading (`tbody tr:nth-child(even)`)

### 3) Mobile card-style collapse at `max-width: 768px`

At viewport widths up to 768px, the CSS switches both schedule and (compact) standings into stacked row cards.

Key mechanics:

- `thead` is visually hidden (accessible clipping pattern)
- `table`, `tbody`, `tr`, and `td` are switched to block layout
- each row gets card styling (border, radius, spacing)
- each cell adds a label via `td::before { content: attr(data-label); }`

Design implication: do not remove `data-label` attributes in custom output/filters, because mobile labels depend on them.

### 4) Standings compact vs full mode on mobile

On mobile, standings support two behaviors:

- **Compact mode (default)**: `.is-priority-low` columns are hidden
- **Full mode**: `.lllm-standings--show-full` keeps all columns and applies a minimum table width

Use full mode when your design needs all metrics visible and horizontal scroll is acceptable.

---

## Class reference by component

## Shared/global classes

- `.lllm-shortcode-heading` — heading above shortcode output blocks
- `.lllm-table-wrap` — responsive table container
- `.lllm-updated` — “last updated” metadata text
- `.lllm-team-logo` — inline team logo image sizing/alignment
- `.lllm-team-name` — inline team name spacing/wrapping behavior
- `.lllm-team-score` — team score text element

## Schedule shortcode classes

Primary container and table classes:

- `.lllm-schedule`

Column and content classes commonly present in schedule output:

- `.date-time`
- `.location`
- `.home`
- `.away`
- `.win`

Date/time fragment classes:

- `.day`
- `.date`
- `.time`

## Standings shortcode classes

Primary container and table classes:

- `.lllm-standings`

Team + metric column classes:

- `.team`
- `.gp`
- `.w`
- `.l`
- `.t`
- `.rf`
- `.ra`
- `.rd`
- `.win-pct`

Mobile visibility helper:

- `.is-priority-low` — hidden in compact mobile standings mode

Optional full-detail mobile modifier:

- `.lllm-standings--show-full`

## Teams shortcode classes

Teams output includes:

- `.lllm-teams`
- `.lllm-team-logo`

(If your theme adds additional wrappers, keep these core classes available for compatibility.)

## Deprecation notice class

- `.lllm-shortcode-deprecation` — deprecation messaging block used for legacy shortcode alias output

---

## Theme override recommendations

1. Scope overrides under a theme namespace where possible (for example `.site-theme-x .lllm-table-wrap ...`) to avoid cross-plugin collisions.
2. Prefer class selectors over element-only selectors.
3. Keep numeric standings columns right-aligned (`.gp`, `.w`, `.l`, `.t`, `.rf`, `.ra`, `.rd`, `.win-pct`) for readability.
4. Preserve nowrap behavior for team/date labels where provided, especially in mobile card mode.
5. If you increase font size substantially, also increase mobile left padding on `td` to prevent overlap with `td::before` labels.

---

## Example override snippet

```css
/* Theme-level polish while preserving plugin responsive logic */
.site-theme-x .lllm-table-wrap .lllm-schedule tr,
.site-theme-x .lllm-table-wrap .lllm-standings tr {
  border-color: rgba(17, 24, 39, 0.15);
}

.site-theme-x .lllm-shortcode-heading {
  font-weight: 700;
  letter-spacing: 0.01em;
}

.site-theme-x .lllm-table-wrap .lllm-standings .win-pct {
  font-feature-settings: "tnum" 1;
}
```

---

## Designer checklist before shipping a new theme

- Verify schedule on desktop + mobile (card collapse behavior).
- Verify standings in both compact mobile and full mobile modes.
- Confirm team logos remain aligned with names.
- Confirm `td::before` labels remain visible and non-overlapping in mobile cards.
- Confirm color contrast for row stripes, borders, and heading text.
