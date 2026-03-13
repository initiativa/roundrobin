# Contributors

Thank you to everyone who has contributed to the RoundRobin plugin for GLPI.

## Original Author

- **Andrea Caracciolo / initiativa s.r.l.** ([@initiativa](https://github.com/initiativa))
  Original plugin author — versions 1.0.x for GLPI 9.5 / 10.0

## GLPI 11 Migration (v2.x)

- **[@loulouontop](https://github.com/loulouontop)**
  GLPI 11 compatibility rewrite, hook system migration, security fixes

- **[@babydunet](https://github.com/babydunet)**
  Supervision, review and testing of the GLPI 11 migration

## Bug Fixes & Improvements (v2.2.0)

- **[@grodriguez-imagunet](https://github.com/grodriguez-imagunet)**
  - Fix: `init()` no longer truncates tables on reinstall (config preserved)
  - Fix: group-level rotation index (fair distribution across categories)
  - Fix: `getLastAssignmentIndex()` returns `null` on first assignment
  - Fix: N+1 queries in `getAll()` replaced with JOIN + bulk COUNT
  - Fix: redundant `item_add` Ticket hook removed
  - Fix: CRLF → LF line endings normalized
  - Feat: Spanish locale (`es_ES`)
  - Refactor: Bootstrap 5 config UI (cards, switches, badges)
  - Refactor: Twig template XSS-safe (`|e` filter, no `|raw`)
  - Docs: README, INSTALLATION_GUIDE, roundrobin.xml updated

---

## How to Contribute

Pull requests are welcome. Please target the `dev/glpi-11` branch.

- Test on a clean GLPI 11 install
- Verify a fresh install and an upgrade from an existing 2.x install
- Check round-robin rotation with at least 2 categories sharing the same group
- Follow GLPI plugin coding standards: https://glpi-developer-documentation.readthedocs.io
