# Changelog - RoundRobin Plugin

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
