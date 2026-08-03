# moodle-local_freshdesk

[![Moodle Plugin CI](https://github.com/verzog/moodle-local_freshdesk/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/verzog/moodle-local_freshdesk/actions/workflows/moodle-ci.yml)

A Moodle local plugin that adds a floating **Get Help** button to every page, opening a support modal powered by your Freshdesk account.

## Features

- Floating **Get Help** button on every Moodle page
- **Auto-suggested articles** appear immediately when the widget opens, based on the current course name and activity type (e.g. quiz, forum, assignment)
- In-modal knowledge base search via the Freshdesk API
- Inline article viewer with option to open the full article in Freshdesk
- **Native contact/ticket form** — no iframe; submits via a Moodle AJAX server-side proxy so the API key never reaches the browser
- Contact form shows the submitting user's **full name, Moodle username, and a profile link**
- Auto-suggested related articles also appear inside the contact form
- Submitted tickets include page URL, course name, user role, Moodle username, profile URL, and Moodle user ID in the description
- User role detection (Staff / Student) based on Moodle course capabilities
- Tiered access via the `local/freshdesk:use` capability — logged-in users get the full in-page widget, guests get a pass-through link to the Freshdesk portal
- Optional setting to hide the widget from site administrators
- Optional **default ticket type, group and agent** settings — required if your Freshdesk account marks the Type, Group or Agent ticket fields as mandatory on submission
- Configurable portal URL, API key, button colour and icon

## Requirements

- Moodle 4.5 or later (CI-tested on 4.5, 5.0, 5.1 and 5.2)
- A Freshdesk account with API access
- The Moodle server must be able to make outbound HTTPS requests to your
  `*.freshdesk.com` domain

## Installation

1. Copy the plugin folder into your Moodle installation:
   ```
   /path/to/moodle/local/freshdesk/
   ```
   > **Moodle 5.x with the `public/` directory layout:** the web root moved, so
   > the correct path is `/path/to/moodle/public/local/freshdesk/`. If the
   > plugin is copied to the old path, Moodle will not detect it at all.

2. Log in as a site administrator and go to:
   **Site Administration → Notifications**
   Moodle will detect the new plugin and run the installer.

3. Go to **Site Administration → Local plugins → Freshdesk Support Widget** to configure the plugin.

## Upgrading from v1.x (local_freshdeskwidget)

Prior to v2.0.0 the plugin was named `local_freshdeskwidget` and installed under `local/freshdeskwidget/`. To upgrade an existing site:

1. Move the plugin folder: `mv local/freshdeskwidget local/freshdesk`
2. Log in as admin and go to **Site Administration → Notifications** to run the upgrade

Moodle treats the rename as a fresh install and runs `db/install.php`, which automatically detects and copies your existing settings (API key, portal URL, colour, etc.) from the old component name — no need to re-enter them.

## Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| Enable widget | Show or hide the widget site-wide | Enabled |
| Freshdesk portal URL | Your Freshdesk account URL | `https://thefeaturecreep.freshdesk.com` |
| Freshdesk API key | Found in Freshdesk under Profile Settings → Your API Key | *(empty)* |
| Default ticket type | Optional. Sent as the ticket `type`; must exactly match one of the values in Freshdesk Admin → Workflows → Ticket Fields → Type | *(empty)* |
| Default group ID | Optional. Numeric Freshdesk group ID to assign new tickets to | *(empty)* |
| Default agent ID | Optional. Numeric Freshdesk agent ID to assign new tickets to | *(empty)* |
| Widget button colour | Hex colour for the Get Help button | `#006B6B` |
| Widget icon | Unicode character or image URL for the button and modal header | 🎓 |
| Hide for site administrators | Suppress the widget for Moodle admins | Disabled |

> **Mandatory Freshdesk fields:** if your Freshdesk account marks the Type,
> Group or Agent fields as *Required when submitting the form*, the API
> rejects tickets that omit them (HTTP 400 `Validation failed` /
> `missing_field`). Either untick the requirement in Freshdesk Admin →
> Workflows → Ticket Fields, or fill in the corresponding default
> type/group/agent settings above. The numeric group and agent IDs are
> visible in the URL when editing them under Freshdesk Admin → Team.

> **Important:** the portal URL must be your `*.freshdesk.com` domain (the
> domain the Freshdesk REST API lives on), and it must be HTTPS. A custom
> support-portal domain (CNAME such as `support.example.com`) will not work,
> because the plugin sends API requests to this URL.

## Access modes

The widget renders in one of two modes, depending on the viewer's
`local/freshdesk:use` capability (granted by default to `user`, `student`,
`teacher`, `editingteacher`, and `manager` archetypes):

- **Full widget** (capability granted): in-page modal with knowledge base
  search, article viewer, and the contact form. All Freshdesk traffic is
  proxied through Moodle AJAX endpoints that re-check the capability
  server-side.
- **Pass-through link** (capability denied — guests, not-logged-in users,
  any role with an explicit deny): the floating "Get Help" button is
  rendered as a link that opens the Freshdesk portal home in a new tab.
  No AJAX endpoints are called, so the proxy attack surface is limited
  to authenticated users with the capability.

Site administrators retain full-widget access (subject to the existing
"Hide for site administrators" setting). To customise access, edit
`local/freshdesk:use` under **Site administration → Users → Permissions →
Define roles**.

## Data sent to Freshdesk

This plugin makes outbound HTTPS requests to the Freshdesk REST API at the
configured portal URL. All API traffic is server-side; the API key never
reaches the browser.

| Endpoint | When | Data sent |
|----------|------|-----------|
| `GET /api/v2/search/solutions` | When the user opens the modal or runs a search | Search term derived from course name / activity type / user input |
| `GET /api/v2/solutions/articles/{id}` | When the user clicks a suggested article | Article ID only |
| `POST /api/v2/tickets` | When the user submits the contact form | Name, email, Moodle username, user ID, profile URL, course name, page URL, role label, ticket subject, message, and optional screenshot |

Connection and read timeouts are bounded (5s connect / 10–20s read) so a slow
or unreachable Freshdesk endpoint never stalls a Moodle page. Requests are
rejected at the proxy layer if the configured portal URL is not HTTPS.

The data sent on ticket submission is also declared via the Moodle Privacy API
(`classes/privacy/provider.php`).

## Improving article suggestions

Suggested articles are driven by the Freshdesk knowledge base search API. To maximise the chance of relevant articles appearing:

- **Publish** articles (drafts are not returned by the search API)
- Set the folder visibility to **All Users** (not Agents Only or Logged-in Users)
- Add **tags** to articles matching your course names and Moodle activity types (`quiz`, `assign`, `forum`, `lesson`, etc.)
- Keep article **titles short and keyword-rich** — the search API weights titles heavily

The widget searches with up to two terms simultaneously: the current course full name and the activity type from the page URL (e.g. on a quiz page inside a named course, it searches both `Introduction to Moodle` and `quiz`).

## Troubleshooting

If the widget is "not working" on a new site, work through these in order:

1. **No Get Help button appears at all**
   - Check **Site administration → Plugins → Local plugins → Freshdesk Support
     Widget**: *Enable widget* must be ticked.
   - If you are logged in as an admin, check *Hide for site administrators* is
     not ticked.
   - Purge all caches (**Site administration → Development → Purge caches**) —
     hook callbacks and AMD JavaScript are both cached.
   - Check the browser's JavaScript console for errors from another plugin or
     theme: a JS exception elsewhere on the page can stop AMD modules loading.
2. **Button appears, but search finds nothing / articles won't load**
   - Confirm the portal URL is your own `https://yourcompany.freshdesk.com`
     domain (not a custom CNAME portal domain, not HTTP).
   - Confirm the API key is correct (Freshdesk → Profile Settings → Your API
     Key) and belongs to an agent with knowledge base access.
   - Articles must be **published** and in folders visible to **All Users**.
   - Enable developer debugging (**Site administration → Development →
     Debugging → DEVELOPER**): the plugin now logs the exact Freshdesk HTTP
     status and response when an API call fails.
   - Confirm the Moodle server can reach Freshdesk:
     `curl -u YOUR_API_KEY:X https://yourcompany.freshdesk.com/api/v2/search/solutions?term=test`
     run from the Moodle server itself (firewalls and proxies on a new host
     are a common cause).
3. **Ticket submission fails**
   - The error dialog's debug info (visible with debugging enabled) includes
     the Freshdesk HTTP status code and response body.
   - HTTP 400 `Validation failed` with `missing_field` errors means your
     Freshdesk account marks Type, Group and/or Agent as mandatory on
     submission — see the **Mandatory Freshdesk fields** note under
     Configuration. This is the most common cause of "Failed to submit
     ticket" while knowledge base search still works.
   - HTTP 401 means a wrong API key — note that API keys are per-agent *and*
     per-portal, so a key from a different Freshdesk account always fails.
     The plugin trims pasted whitespace from the key automatically.
   - HTTP 403 means the agent lacks permission to create tickets.

## Releasing a new version

This plugin is distributed via [Moodle Marketplace](https://marketplace.moodle.com/)
(which replaced the `moodle.org/plugins` directory on 2026-07-20).

> **Publishing is a manual step.** Moodle Marketplace does **not** support API
> or GitHub-workflow uploads at launch — new plugin versions must be uploaded
> by hand through the Marketplace **Plugin dashboard**. A version bump merged to
> `main` therefore does **not** reach users on its own. Restoring automated
> uploads is on Moodle's roadmap; until then, always complete the manual upload
> below.

To cut a release:

1. Bump both `$plugin->version` (the integer date stamp) and `$plugin->release`
   (the human version) in `version.php`, add a matching
   `upgrade_plugin_savepoint` block in `db/upgrade.php`, and merge to `main`.
2. On merge, **`release.yml`** tags the commit `vX.Y.Z`, builds the plugin ZIP,
   and creates a matching GitHub release. This produces the artefact to upload —
   it does not publish to the Marketplace.
3. **Upload the ZIP** from that GitHub release as a new version on the plugin's
   Moodle Marketplace Plugin dashboard, and add the release notes.

Sites that installed the plugin from the Marketplace are then notified of the
new version. (`moodle-release.yml` is retained but dormant — it targeted the
retired `moodle.org` web service and is kept only as scaffolding for a future
Marketplace upload API.)

## Building the JavaScript

`amd/build/widget.min.js` must be regenerated whenever `amd/src/widget.js`
changes. From a Moodle checkout:

```bash
cd /path/to/moodle
npm install
grunt amd --root=local/freshdesk
```

(or minify `amd/src/widget.js` with terser into `amd/build/widget.min.js`).

## File Structure

```
local/freshdesk/
├── amd/
│   ├── build/
│   │   └── widget.min.js                    # Built AMD module (loaded by Moodle)
│   └── src/
│       └── widget.js                        # Source AMD module
├── classes/
│   ├── event/
│   │   └── ticket_submitted.php             # Audit event fired on successful submission
│   ├── external/
│   │   ├── get_article.php                  # AJAX external function (article fetch proxy)
│   │   ├── search_articles.php              # AJAX external function (search proxy)
│   │   └── submit_ticket.php                # AJAX external function (ticket submission proxy)
│   ├── hook/
│   │   └── output/
│   │       └── before_footer.php            # Hook callback — injects widget config and AMD module
│   └── privacy/
│       └── provider.php                     # Privacy API provider (declares external data flow)
├── db/
│   ├── access.php                          # Capability definitions (local/freshdesk:use)
│   ├── caches.php                           # Cache definitions
│   ├── hooks.php                            # Registers the before_footer hook callback
│   ├── install.php                          # Sets default config values on install
│   ├── services.php                         # Registers AJAX external functions
│   └── upgrade.php                          # Upgrade steps between versions
├── lang/
│   └── en/
│       └── local_freshdesk.php              # English language strings
├── templates/
│   ├── help_button.mustache                 # Floating Get Help button
│   └── modal.mustache                       # Support modal markup
├── lib.php
├── settings.php                             # Admin settings page
├── version.php                              # Plugin metadata
└── README.md
```

## Bug reports and contributing

Issues and pull requests are welcome at
[github.com/verzog/moodle-local_freshdesk](https://github.com/verzog/moodle-local_freshdesk).
When reporting a bug, please include your Moodle version, the plugin
version (from `version.php` `$plugin->release`), and the relevant
contents of your browser's JavaScript console.

## License

This plugin is licensed under the [GNU General Public License v3 or later](http://www.gnu.org/copyleft/gpl.html).


## Disclaimer

[Freshdesk](https://freshdesk.com) is a commercial product owned by Freshworks Inc. This plugin is an independent, community-developed integration and is not affiliated with, endorsed by, or supported by Freshworks Inc. Use of Freshdesk requires a separate account and is subject to [Freshworks' own terms of service and pricing](https://www.freshworks.com/terms/).
