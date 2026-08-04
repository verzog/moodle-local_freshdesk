# Changelog

All notable changes to the Freshdesk Support Widget (`local_freshdesk`) are
recorded here. Versions match `$plugin->release` in `version.php`. The section
for each release is used verbatim as that version's GitHub release notes and can
be pasted into the Moodle Marketplace release-notes field.

## 2.4.8 - 2026-08-03

Rolls up all changes since 2.4.5, adding full Moodle 5.x support and new
ticket-routing options.

### New
- **Optional default ticket Type, Group and Agent.** Set a default ticket type,
  Freshdesk group ID and/or agent ID in the plugin settings. Required if your
  Freshdesk account marks the Type, Group or Agent fields as *mandatory* on
  submission — such accounts previously rejected tickets with an HTTP 400
  validation error.

### Improved
- **Clearer failure diagnostics.** When a knowledge base search or ticket
  submission fails, the plugin now surfaces the exact Freshdesk HTTP status and
  response, making a wrong API key, portal URL or mandatory-field error far
  easier to diagnose.
- **Whitespace tolerance.** Whitespace accidentally pasted into the API key or
  portal URL is trimmed automatically.
- **Smarter screenshot paste.** Pasting an image (Ctrl/⌘+V) is only captured
  while the contact form is open, so it no longer interferes with pasting
  elsewhere on the page.

### Compatibility
- **Moodle 5.x support.** Verified on Moodle 4.5, 5.0, 5.1 and 5.2 across
  PHP 8.1–8.4. Internal APIs updated for Moodle 5.x (`js_call_amd` config
  passing, `core_cache` / `core\context` namespaces).

### Maintenance
- Release and CI tooling updates. Automated publishing to the retired
  `moodle.org` plugins directory was removed after the Moodle Marketplace
  migration; new versions are now uploaded manually via the Marketplace Plugin
  dashboard. No change to the widget itself in 2.4.8.

## 2.4.7 - 2026-06-11

### New
- Optional default ticket type, group ID and agent ID settings for Freshdesk
  accounts that make those fields mandatory on submission.

### Improved
- Trim whitespace from the stored API key and portal URL.

### Compatibility
- CI matrix expanded to cover Moodle 5.0, 5.1 and 5.2.

## 2.4.6 - 2026-06-11

### Compatibility
- Moodle 5.x compatibility pass: widget config passed via `js_call_amd` (the
  deprecated `data_for_js` was removed) and cache/context classes moved to the
  `core_cache` / `core\context` namespaces.

### Improved
- Freshdesk API failures now emit `debugging()` diagnostics so administrators
  can see why search or ticket submission failed.
- Clipboard screenshot paste is captured only while the contact form is open.

## 2.4.5 - 2026-06-06

- Baseline version currently published on the Moodle plugin listing. Earlier
  history predates this changelog; see the Git tags and
  `db/upgrade.php` for per-version upgrade notes.
