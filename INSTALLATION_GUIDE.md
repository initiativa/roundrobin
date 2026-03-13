# RoundRobin Plugin — Installation & Configuration Guide

## What this plugin does

RoundRobin automatically assigns new tickets to technicians in a rotating, fair order. When a ticket is created with an enabled ITIL category, the plugin picks the next technician from the category's group and assigns the ticket — no manual triage needed.

The rotation counter is **shared per group**, not per category. This means that if you have two categories ("Hardware" and "Software") both linked to the "Support" group, tickets alternate fairly among all group members regardless of which category they come from.

---

## Requirements

| | |
|---|---|
| **GLPI** | 11.0.0 to 11.0.99 |
| **PHP** | 8.1 or higher |
| **Database** | MySQL / MariaDB (via GLPI) |

---

## Installation

### Step 1 — Upload the plugin

Copy the `roundrobin` folder into your GLPI plugins directory:

```
/var/www/html/glpi/plugins/roundrobin/
```

On Linux, set correct ownership:

```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/roundrobin
chmod -R 755 /var/www/html/glpi/plugins/roundrobin
```

### Step 2 — Install and enable in GLPI

1. Log in as a GLPI administrator.
2. Go to **Setup → Plugins**.
3. Find **Round Robin** and click **Install**.
   - Three database tables are created automatically.
   - All existing ITIL categories are imported (disabled by default).
4. Click **Enable**.

---

## Upgrading

### From version 1.0.x

> ⚠️ There is no automatic migration from 1.x. You must reinstall.

1. Go to **Setup → Plugins** and click **Uninstall** on the old version.
2. Replace the `roundrobin` folder with the new version.
3. Click **Install → Enable**.
4. Reconfigure your categories (see Configuration below).

### From version 2.x

Just replace the plugin files. The install routine is idempotent:

- Creates the new `rr_groups` table if it doesn't exist.
- Migrates schema (drops legacy `last_assignment_index` column from assignments table).
- **Never touches your existing `is_active` settings.**

---

## Configuration

### Step 1 — Link a group to an ITIL category

1. Go to **Setup → Dropdowns → ITIL Categories**.
2. Edit the category you want to use with round-robin.
3. Set the **"Group in charge of the hardware"** field to the group whose members should receive tickets.
4. Save.

Repeat for each category you want to enable.

### Step 2 — Open the plugin configuration

Go to **Setup → Plugins**, find **Round Robin**, and click the **wrench icon** (Configure).

You'll see two sections:

#### Global options

**Assign also to the original Group** (toggle)

| State | Behaviour |
|---|---|
| **On** (recommended) | Ticket is assigned to both the individual technician AND the group. Other team members can see it in the group queue. |
| **Off** | Only the individual technician is assigned. |

Click **Save** after changing this option.

#### Per-category table

Each row shows an ITIL category with:

| Column | Meaning |
|---|---|
| **ITIL Category** | Full category path. |
| **Group** | The group linked to this category. Blue badge = group set. Grey italic = no group. |
| **Members** | Active member count. Green badge = at least one member. Yellow = zero members. |
| **Round Robin** | Toggle switch. Only available if the category has a group with at least one active member. |

Enable the categories you want to use round-robin on, then click **Save**.

---

## How rotation works

### Group-level counter

The plugin stores a single rotation index per **group**, shared across all categories that use that group.

**Example:** 3 technicians (Alice, Bob, Charlie) in group "Support". Categories "Hardware" and "Software" both use this group.

| Ticket | Category | Group counter | Assigned to |
|---|---|---|---|
| 1 | Hardware | 0 | Alice |
| 2 | Software | 1 | Bob |
| 3 | Hardware | 2 | Charlie |
| 4 | Software | 0 | Alice |
| 5 | Hardware | 1 | Bob |

Without group-level tracking, Alice would receive the first ticket from *every* category — this was a bug in earlier versions.

### Active users only

The plugin queries only users where `is_active = 1` and `is_deleted = 0`. If a technician leaves or is deactivated, they are automatically skipped in the next rotation without any reconfiguration needed.

### First assignment

The first ticket to a group starts at index 0 (first member by `glpi_groups_users.id` order, which corresponds to the order they were added to the group).

---

## Troubleshooting

### Tickets aren't being assigned

Work through this checklist:

1. **Plugin enabled?** — Setup → Plugins → Round Robin should show "Enabled".
2. **Category enabled in plugin config?** — Check the per-category toggle is on.
3. **Category has a group?** — Setup → Dropdowns → ITIL Categories → edit → "Group in charge of the hardware".
4. **Group has active members?** — Administration → Groups → open group → Users tab. Members badge in plugin config shows the count.
5. **Users are active?** — Administration → Users → check `is_active`.

### Same person keeps getting assigned

- Only one active user in the group — add more members.
- Other members may be inactive or deleted.

### Configuration page shows "No group assigned" for all categories

The ITIL category was not linked to a group. See Step 1 of Configuration above.

### Checking logs

```bash
# GLPI debug log (if debug mode is on)
tail -f /var/www/html/glpi/files/_log/php-errors.log | grep -i roundrobin
```

To enable debug logging for the plugin, set `$PLUGIN_ROUNDROBIN_ENV = 'development'` in `inc/config.class.php` (do not leave this on in production).

---

## Database reference

| Table | Purpose |
|---|---|
| `glpi_plugin_roundrobin_rr_assignments` | One row per ITIL category. Stores `is_active`. |
| `glpi_plugin_roundrobin_rr_groups` | One row per group. Stores `last_assignment_index`. |
| `glpi_plugin_roundrobin_rr_options` | One row. Stores `auto_assign_group` global option. |

---

## Getting help

- **GitHub Issues:** https://github.com/initiativa/roundrobin/issues
- Include your GLPI version, PHP version, and any relevant log lines.
