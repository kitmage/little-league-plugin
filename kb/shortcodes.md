# Using Shortcodes in Little League Plugin

Shortcodes are a quick way to drop plugin content into any WordPress page or post without writing custom code.

If you're new to shortcodes, don't worry—just copy, paste, and publish.

## What is a shortcode?

A shortcode is a small piece of text in square brackets, like this:

```text
[little_league_schedule]
```

When WordPress sees that text, it replaces it with live content from the plugin.

## Where to add shortcodes

You can add shortcodes in:

- Pages
- Posts
- Widget areas that support shortcode blocks
- The Shortcode block in the block editor

## Common shortcode examples

Use these examples to get started:

```text
[little_league_schedule]
[little_league_standings]
[little_league_team id="12"]
```

> Tip: If your site uses the block editor, add a **Shortcode** block, then paste one shortcode per block.

## Using shortcode attributes

Some shortcodes accept options (called attributes). These let you control what appears.

Example:

```text
[little_league_team id="12" show_logo="true"]
```

In this example:

- `id="12"` selects a specific team
- `show_logo="true"` turns on the team logo

Always use straight quotes (`"`) around attribute values.

## Best practices

- Start with one shortcode and publish to test.
- Keep shortcodes on their own line for easier editing.
- Double-check spelling—shortcode names must match exactly.
- If nothing appears, make sure the plugin is active.

## Troubleshooting

If a shortcode displays as plain text instead of content:

1. Confirm the plugin is activated.
2. Make sure the shortcode starts with `[` and ends with `]`.
3. Check attribute names for typos.
4. Try removing attributes to test the basic shortcode first.

If you're still stuck, contact your site administrator with the exact shortcode you used and where you added it.

---

Need help? Start simple, test one shortcode at a time, and you'll be up and running quickly.
