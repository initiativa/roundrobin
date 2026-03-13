# RoundRobin — GLPI Plugin

> Automatic, fair ticket assignment for your support team.

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![GLPI 11](https://img.shields.io/badge/GLPI-11.0.x-orange)](https://glpi-project.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)

---

## What it does

RoundRobin distributes incoming tickets evenly among the technicians of a group. Each new ticket on an enabled ITIL category is automatically assigned to the next technician in line — no manual triage needed.

```
Ticket 1 → Alice
Ticket 2 → Bob
Ticket 3 → Charlie
Ticket 4 → Alice   ← cycle repeats
```

### Key behaviour

- **Group-level counter** — the rotation index is shared across all categories that point to the same group. If "Hardware" and "Software" both use the "Level-1 Support" group, tickets alternate fairly between all members regardless of which category they arrive on.
- **Active users only** — inactive or deleted users are automatically skipped.
- **Optional group assignment** — optionally sets the group itself as assignee alongside the individual, so teammates can see the ticket in their queue.
- **Non-destructive install** — reinstalling or upgrading the plugin never resets your category configuration.

---

## Requirements

| Requirement | Version |
|---|---|
| GLPI | 11.0.0 – 11.0.99 |
| PHP | 8.1 or higher |
| Database | MySQL / MariaDB (via GLPI) |

For GLPI 9.5 / 10.0 use the [1.0.9 release](https://github.com/initiativa/roundrobin/releases/tag/1.0.9).

---

## Installation

### From the GLPI marketplace / plugin directory

1. Go to **Setup → Plugins**.
2. Search for *RoundRobin* and click **Install**.
3. Click **Enable**.

### Manual installation

```bash
cd /var/www/html/glpi/plugins
# Download the release archive
wget https://github.com/initiativa/roundrobin/releases/download/2.2.0/roundrobin-2.2.0.tar.gz
tar -xzf roundrobin-2.2.0.tar.gz
chown -R www-data:www-data roundrobin
```

Then go to **Setup → Plugins**, find *RoundRobin*, and click **Install → Enable**.

### Upgrading from 1.0.x

1. Uninstall the old plugin from **Setup → Plugins** (this drops the old tables).
2. Replace the `roundrobin` folder with the new version.
3. Install and enable from **Setup → Plugins**.
4. Reconfigure your categories.

### Upgrading from 2.x

Just replace the files and re-enable. The install routine is idempotent — it adds the new `rr_groups` table and migrates schema without touching your existing configuration.

---

## Configuration

### 1 — Link a group to an ITIL category

Go to **Setup → Dropdowns → ITIL Categories**, edit a category, and set the **"Group in charge of the hardware"** field to the group whose members should receive the tickets.

### 2 — Enable round-robin for that category

Go to **Setup → Plugins → Round Robin → Configure** (wrench icon).

| Setting | Description |
|---|---|
| **Assign also to the original Group** | If enabled, the group is set as assignee alongside the individual technician. Useful so other team members can see the ticket in the group queue. |
| **Per-category toggle** | Enable or disable round-robin independently for each category. Categories without a group, or groups without active members, cannot be enabled. |

### 3 — Test it

Create a ticket with an enabled category. Check the *Assigned to* field — it should show the first technician. Create another ticket in the same category (or in a different category that uses the same group) — it should show the next technician.

---

## How the rotation works

The plugin stores one rotation counter **per group** in the `glpi_plugin_roundrobin_rr_groups` table. All categories sharing the same group advance the same counter.

**Example:** Categories "Hardware" and "Software" both use group "Support" with members Alice, Bob, Charlie.

| Event | Group counter | Assigned to |
|---|---|---|
| Ticket on Hardware | 0 → Alice | Alice |
| Ticket on Software | 1 → Bob | Bob |
| Ticket on Hardware | 2 → Charlie | Charlie |
| Ticket on Software | 0 → Alice | Alice |

Without group-level tracking, "Hardware" and "Software" would each have their own counter and Alice would receive the first ticket from *every* category.

---

## Database tables

| Table | Purpose |
|---|---|
| `glpi_plugin_roundrobin_rr_assignments` | Per-category config (`is_active`). |
| `glpi_plugin_roundrobin_rr_groups` | Per-group rotation index (`last_assignment_index`). |
| `glpi_plugin_roundrobin_rr_options` | Global options (`auto_assign_group`). |

---

## Troubleshooting

**Tickets aren't being assigned**
- Plugin enabled? **Setup → Plugins**.
- Category enabled in the plugin config?
- Category has a group set? **Setup → Dropdowns → ITIL Categories**.
- Group has active members? **Administration → Groups → Users tab**.

**Same person keeps getting assigned**
- Only one active member in the group.
- Other members may be inactive. Check **Administration → Users**.

**Check the logs**
```bash
tail -f /var/www/html/glpi/files/_log/php-errors.log | grep -i roundrobin
```

---

## Contributing

Pull requests are welcome. Please target the `dev/glpi-11` branch.

Before submitting:
- Test on a clean GLPI 11 install.
- Run a fresh install and an upgrade from an existing 2.x install.
- Verify round-robin rotation with at least 2 categories sharing the same group.

---

## Languages

| Language | Code | Status |
|---|---|---|
| English | `en_US` | ✅ Complete |
| German | `de_DE` | ✅ Complete |
| French | `fr_FR` | ✅ Complete |
| Italian | `it_IT` | ✅ Complete |
| Polish | `pl_PL` | ✅ Complete |
| Spanish | `es_ES` | ✅ Complete |

To add a new language, copy `locales/en_US.po`, translate the `msgstr` lines, compile with `msgfmt`, and open a PR.

---

## Authors

- **Andrea Caracciolo / initiativa srl** — original author
- **loulouontop** — GLPI 11 compatibility rewrite
- **babydunet** — supervision & review
- Community contributors

## License

[GNU General Public License v3.0](LICENSE)
