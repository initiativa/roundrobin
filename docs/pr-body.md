## Summary

This adds an extension point so other plugins can decide **which group members are eligible** for a round-robin assignment, and makes the rotation skip ineligible members without shifting everybody else's turn.

- New hook `$PLUGIN_HOOKS['roundrobin_filter_members'][<plugin>] = callable(array $params): array`. The callback receives `['members' => rows from getGroupsUsersByCategory(), 'itilcategories_id' => int, 'groups_id' => int]` and returns the same array with `members` filtered. Callbacks are included on demand with `Plugin::includeHook()` (same as `Plugin::doHookFunction()`), and a callback that throws is logged and ignored so it never blocks ticket creation.
- New pure helper `PluginRoundRobinRotation::pickNextIndex()` (unit-tested): keeps `last_assignment_index` over the **full** member list and advances to the next eligible member, so an absent technician gets their turn back when they return.
- When nobody is eligible the ticket is left without a technician. Before this change `plugin_roundrobin_hook_item_add_handler()` inserted a `Ticket_User` row with a `NULL` user when `findUserIdToAssign()` returned `null`.
- `assignTicket()` now delegates to `findUserIdToAssign()` (single rotation implementation).
- README section documenting the hook; version bumped to 1.1.0; `composer.json`/`phpunit.xml` for the tests.

Use case: the [Presence](https://github.com/Facil-Group/glpi-presence) plugin (Teams-like status: Available / Busy / Do not disturb / Be right back / Appear away / Offline) registers a filter so only technicians whose status is *Available* receive tickets.

## Test plan

- `phpunit` (9 tests on the rotation helper).
- Manual on GLPI 10.0.26 (Docker): group of 3 technicians, one in "Do not disturb" → tickets go 1, 3, 1; back to Available → next ticket goes to 2; all unavailable → ticket created without assignee; full rotation → 2, 3, 1, 2.
- Without any plugin registering the hook, behaviour is unchanged (all members eligible).

🤖 Generated with [Claude Code](https://claude.com/claude-code)

https://claude.ai/code/session_01UnUbdLTDPNpF6Tx2wxSXq7
