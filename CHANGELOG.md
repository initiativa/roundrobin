# Changelog - RoundRobin Plugin

## [2.3.0] - 2026-06-25

### Highlights
- **Configuration save:** Single form with one **Save** button (below both sections) for general options and category toggles; one unified success or no-change message after submit.
- **Large category lists:** GLPI-style **pagination** (rows / page, row count, page navigation); selected page size is remembered per user session across saves.
- **Search:** Compact search field on the category table (filter by category or technician group name); placeholder translated in all plugin locales.
- **Usability:** Scrollable category table when more than five rows are visible on the current page; pagination controls aligned like GLPI lists.

### Requirements
- Unchanged: GLPI 11.0.x, PHP ≥ 8.1.

## [2.2.0] - 2026-05-11

### Highlights
- **Locales:** All plugin UI strings synchronized across `en_GB`, `en_US`, `en`, `fr_FR`, `de_DE`, `it_IT`, and `pl_PL`; translation template `roundrobin.pot` updated for maintainers.
- **Spanish variants:** Added Spanish locale variants so Spanish regions keep Spanish translations instead of falling back to another language.
- **Configuration UI:** RoundRobin-themed header, grouped sections (group assignment behaviour, categories listing), readable item links using theme link tokens.
- **Config usability:** Added **Select all** for category switches, and switch labels now show **Enabled/Disabled** correctly.
- **Access:** Entry under **Setup** (alongside Plugins → Configure) for the configuration page.
- **Persistence:** Plugin tables were retained on uninstall so a reinstall kept category toggles and options (superseded in Unreleased).
- **Hooks:** `pre_item_update` on Ticket applies Round Robin when `itilcategories_id` changes (skipped if the ticket already has an assignee). `item_update` on ITILCategory re-syncs after restore and resets group rotation when the category’s group changes.
- **API hygiene:** `getGroupByItilCategory()` now returns `?int` (null instead of `false`); group actors only added when `group_id` is a positive int.
- **Logging:** DEBUG file logs are suppressed unless `PluginRoundRobinConfig::$PLUGIN_ROUNDROBIN_ENV === 'development'`.
- **Dead code:** `PluginRoundRobinRequest::getCurrentProfileSettings()` no longer queries a non-existent table (returns `null`).

### Requirements
- Unchanged : GLPI 11.0.x, PHP ≥ 8.1.



## [2.1.0] - 2025-01-14

### 🎉 GLPI 11 Compatibility - Complete Rewrite

**Contributors:**
- [@loulouontop](https://github.com/loulouontop) - Development & Testing
- [@babydunet](https://github.com/babydunet) - Supervision & Review

### ✨ Major Changes

#### GLPI 11 Compatibility
- ✅ Updated hook system to use GLPI 11 object-based parameters (not arrays)
- ✅ Changed `pre_item_add` hook to receive `$item` object instead of `$input` array
- ✅ Updated all database operations to use `doQueryOrDie()` instead of deprecated `query()`
- ✅ Migrated logging from `error_log()` to `Toolbox::logInFile()`
- ✅ Updated CSRF token handling for GLPI 11 compatibility
- ✅ Fixed output escaping using proper `htmlspecialchars()` methods
- ✅ Added permission checks using `Session::checkRight()`

#### Round-Robin Logic Improvements
- 🔄 **Changed rotation tracking from per-category to per-group**
  - Previous: Each category had its own rotation counter (unfair distribution)
  - Now: All categories sharing the same group use one shared counter (fair distribution)
- ✅ Added active user filtering (skips inactive/deleted users automatically)
- ✅ Improved NULL index handling for first assignments
- ✅ Added consistent user ordering for predictable rotation

#### Security Enhancements
- 🔐 Fixed SQL injection vulnerabilities in table operations
- 🔐 Fixed XSS vulnerabilities with proper output escaping
- 🔐 Added CSRF protection on all forms
- 🔐 Added input validation on all POST data
- 🔐 Added permission checks on configuration page

#### Performance Optimizations
- ⚡ Optimized database queries (removed N+1 query problems)
- ⚡ Added database indexes for faster lookups
- ⚡ Improved query structure with proper LIMIT clauses

#### Database Changes
- 📊 Updated table creation to use standard GLPI 11 methods
- 📊 Added proper charset and collation (utf8mb4_unicode_ci)
- 📊 Improved index structure for better performance

#### Bug Fixes
- 🐛 Fixed form submission redirects
- 🐛 Fixed configuration page save functionality
- 🐛 Fixed ticket assignment actor format for GLPI 11
- 🐛 Fixed empty group handling
- 🐛 Fixed category synchronization on install

### 📝 Technical Details

#### Files Modified
- `setup.php` - Complete rewrite for GLPI 11
- `hook.php` - Rebuilt hook handlers with new logic
- `inc/RRAssignmentsEntity.class.php` - Optimized queries, added group-based methods
- `front/config.form.php` - Fixed security issues and GLPI 11 compatibility
- `inc/logger.class.php` - Updated to use GLPI logging
- `plugin.xml` - Updated version and requirements

#### New Methods Added
- `getLastAssignmentIndexByGroup($groupId)` - Get rotation index by group
- `updateLastAssignmentIndexByGroup($groupId, $index)` - Update all categories using same group

#### Requirements
- GLPI: 11.0.0 to 11.0.99
- PHP: 8.1+
- Database: MySQL/MariaDB (via GLPI)

### 🧪 Testing
- ✅ Tested on GLPI 11.0.x
- ✅ Verified round-robin rotation works correctly
- ✅ Tested with multiple groups and categories
- ✅ Verified edge cases (empty groups, single user, inactive users)
- ✅ Security tested (SQL injection, XSS, CSRF)
- ✅ Performance tested with large category sets

### 📚 Documentation
- Created comprehensive INSTALLATION_GUIDE.md
- Created production-ready README_PRODUCTION.md
- Added inline code comments
- Documented all new methods

---

## [1.0.9] - Previous Release
- Last version compatible with GLPI 9.5 and 10.0
- See original repository for older changelog

---

**Note:** Version 2.1.0 is a major rewrite for GLPI 11 compatibility. If upgrading from 1.0.x, you must uninstall the old version first and reconfigure the plugin after installing 2.1.0.

---