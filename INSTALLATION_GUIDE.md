# RoundRobin Plugin - Installation & Configuration Guide

## 📋 What This Plugin Does

The RoundRobin plugin automatically assigns tickets to technicians in a fair, rotating pattern. When users create tickets with specific categories, the plugin automatically picks the next technician in line and assigns the ticket to them.

**Example:**
- You have 3 technicians: Alice, Bob, and Charlie
- First ticket → Alice
- Second ticket → Bob
- Third ticket → Charlie
- Fourth ticket → Alice (cycle repeats)

---

## ✅ Requirements

Before installing, make sure you have:
- **GLPI Version:** 11.0.0 to 11.0.99
- **PHP Version:** 8.1 or higher
- **Database:** MySQL/MariaDB (already part of GLPI)
- **User Permissions:** GLPI Administrator access

---

## 📦 Installation Steps

### Step 1: Upload the Plugin

1. **Locate your GLPI plugins folder:**
   - Usually: `/var/www/html/glpi/plugins/` (Linux)
   - Or: `C:\xampp\htdocs\glpi\plugins\` (Windows)

2. **Copy the `roundrobin` folder:**
   ```
   Copy the entire "roundrobin" folder into the plugins directory
   ```

3. **Set correct permissions (Linux only):**
   ```bash
   cd /var/www/html/glpi/plugins/
   chown -R www-data:www-data roundrobin
   chmod -R 755 roundrobin
   ```

### Step 2: Install the Plugin in GLPI

1. **Log into GLPI** as an administrator

2. **Navigate to:**
   ```
   Setup → Plugins
   ```

3. **Find "RoundRobin"** in the list

4. **Click "Install"**
   - The plugin will create three database tables (assignments per category, options, rotation index per technician group)
   - All your existing ITIL categories will be imported

5. **Click "Enable"**
   - The plugin is now active

---

## ⚙️ Configuration

### Step 3: Configure RoundRobin

1. **Open configuration** (either path):
   ```
   Setup → RoundRobin
   ```
   or
   ```
   Setup → Plugins → RoundRobin → Configure (wrench)
   ```

2. **You'll see:**
   - A **RoundRobin** heading and summary text explaining that new tickets take the **next technician from the category’s technician group**.
   - **Group assignment behaviour** — whether tickets are also assigned to the linked **technician group** (in addition to the rotated user).
   - **Categories & technician groups** — a table listing each ITIL category (with clickable names), linked group, active member count and **settings** switches.

3. **Enable round-robin for specific categories**
   - You can only enable a row if the category has a **technician group** set in GLPI.
   - **Save** after changing toggles.

> **Languages:** Interface strings are translated in `en_GB`, `en_US`, `fr_FR`, `de_DE`, `it_IT`, and `pl_PL`. Set your profile language in GLPI to use them.

### Uninstall / reinstall

Removing the plugin from **Setup → Plugins** does **not** drop RoundRobin tables; reinstalling restores the plugin with your previous category toggles and options still in the database. To fully remove stored data you would truncate or drop those tables manually in MySQL/MariaDB (advanced).

---

## 🎯 How to Set Up Your First Category

### Example: Laptop Issues

Let's say you want all laptop issues to be automatically assigned in round-robin fashion.

#### Step 1: Create/Verify the Group

1. **Go to:** `Administration → Groups`
2. **Create or select a group** (e.g., "Laptop Support Team")
3. **Add technicians** to this group:
   - Click the group name
   - Go to "Users" tab
   - Add Alice, Bob, and Charlie

#### Step 2: Link the Group to the ITIL Category

1. **Go to:** `Setup → Dropdowns → ITIL Categories`
2. **Find "Laptop Issues"** (or create it)
3. **Edit the category**
4. **Set "Group in charge of the hardware"** to "Laptop Support Team"
5. **Save**

#### Step 3: Enable RoundRobin for This Category

1. **Go to:** `Setup → RoundRobin` (or `Setup → Plugins → RoundRobin` → Configure)
2. **Find "Laptop Issues"** in the table
3. **You should see:**
   - Category: "Laptop Issues"
   - Group: "Laptop Support Team"
   - Member count listed for the technician group
4. **Turn the setting on** for that row (requires the category to have the technician group set)
5. **Click Save**

#### Step 4: Test It!

1. **Create a test ticket:**
   - Go to `Assistance → Create a ticket`
   - Set Category to "Laptop Issues"
   - Submit the ticket

2. **Check the assignment:**
   - Open the ticket
   - Look at "Assigned to" → You should see one of your technicians!

3. **Create another test ticket:**
   - Same category
   - The NEXT technician in the rotation should be assigned

---

## 🔧 Troubleshooting

### Problem: Tickets aren't being assigned automatically

**Check these things:**

1. **Is the plugin enabled?**
   - `Setup → Plugins` → RoundRobin should show "Enabled"

2. **Is the category enabled?**
   - Go to plugin config
   - Check if that specific category is set to "Enabled"

3. **Does the category have a group?**
   - `Setup → Dropdowns → ITIL Categories`
   - Edit the category
   - Check "Group in charge of the hardware" is set

4. **Does the group have members?**
   - `Administration → Groups`
   - Open the group
   - Check there are users in the "Users" tab

5. **Are the users active?**
   - Users must be active (not deleted or disabled)
   - Check: `Administration → Users`

### Problem: Same person keeps getting assigned

**Possible causes:**
- Only one active user in the group
- Other users are disabled/inactive
- Check group membership

### Problem: Error when saving configuration

**Possible causes:**
- Permission issue
- Make sure you're logged in as admin
- Try: `Setup → General → Check → "Check database integrity"`

### Problem: Plugin won't install

**Check:**
- GLPI version (must be 11.0.0 to 11.0.99)
- PHP version (must be 8.1+)
- Database connection is working
- File permissions (Linux)

---

## 📊 Understanding the Rotation

### How It Works

The plugin remembers which technician was assigned last for each category.

**Example with 3 technicians: Alice, Bob, Charlie**

| Ticket # | Category | Assigned To | Reason |
|----------|----------|-------------|--------|
| 1 | Laptop Issues | Alice | First assignment |
| 2 | Laptop Issues | Bob | Next in rotation |
| 3 | Laptop Issues | Charlie | Next in rotation |
| 4 | Laptop Issues | Alice | Back to start |
| 5 | Printer Issues | Bob | Different category, has its own rotation |

**Key Points:**
- Each category has its own rotation
- If someone is removed from the group, rotation adjusts automatically
- If someone is added to the group, they'll be included in rotation

---

## 🔐 Security Notes

- Only GLPI administrators can configure the plugin
- The plugin respects all GLPI permission systems
- All database operations are protected against SQL injection
- CSRF tokens protect the configuration form

---

## 📞 Getting Help

If you still have issues:

1. **Check GLPI logs:**
   - `files/_log/` directory
   - Look for lines containing "RoundRobin"

2. **Enable debug mode:**
   - Edit `config/config_db.php`
   - Look for debug settings

3. **GitHub Issues:**
   - https://github.com/initiativa/roundrobin/issues

---

## 🎉 You're Done!

Your RoundRobin plugin is now configured and ready to use. Every time a ticket is created with an enabled category, it will automatically be assigned to the next technician in rotation!

**Pro Tip:** Start with one or two categories first, test thoroughly, then enable more categories once you're comfortable with how it works.
