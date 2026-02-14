# Changelog

## Unreleased

### Migration notes
- Playoff schedule filtering now keeps compatibility with legacy game rows that only contain `playoff_round` / `playoff_slot` metadata. Those rows continue to appear under playoff schedule output until all legacy data is normalized.

### Deprecation notes
- Legacy shortcode `[lllm_playoff_bracket]` is now soft-deprecated (not hard removed).
- The legacy shortcode currently aliases to `[lllm_schedule type="playoff"]` to preserve front-end output.
- Admins receive a deprecation notice to migrate existing content to the schedule shortcode.

### QA regression checklist
- Create regular and playoff games manually.
- Import regular and playoff games from CSV.
- Confirm regular schedule excludes playoff games.
- Confirm playoff schedule includes only playoff games.
- Confirm shortcode generator outputs valid schedule shortcodes with `type`.
