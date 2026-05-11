# RoundRobin — GLPI plugin

**RoundRobin** distributes incoming tickets fairly by assigning each new ticket to the next active technician in the **technician group** linked to the ticket’s ITIL category (round-robin). Optionally, the ticket can also be assigned to that group.

Configure the technician group per category (**ITIL category** form: technician group field). Enable round-robin per category on the plugin configuration page.

## GLPI 11

Current release targets **GLPI 11.0.x** and **PHP 8.1+**.

- Assignment uses GLPI 11 **`_actors`** on ticket creation (`pre_item_add`).
- Configuration UI uses **Twig** templates (`templates/config.form.twig`, `@roundrobin` namespace).
- **Setup → RoundRobin** opens the configuration page (also available via **Setup → Plugins** → wrench **Configure**).
- Rotation is tracked **per group** so categories sharing one group share one fair rotation sequence.

Translations are shipped under `locales/` (`en_GB`, `en_US`, `fr_FR`, `de_DE`, `it_IT`, `pl_PL`; see `roundrobin.pot`).

See **INSTALLATION_GUIDE.md** for step-by-step install and behaviour notes (including reinstall with preserved DB tables).

<<<<<<< Updated upstream
## GLPI 11 Compatibility

Version 2.0.0 adds support for GLPI 11.x:
- Updated to use GLPI 11 actor system (`_actors` array) for ticket assignment
- Uses GLPI's DB framework methods instead of raw SQL queries
- Compatible with PHP 8.1+
- Uses Twig templates for configuration form

enjoy!
=======
>>>>>>> Stashed changes
