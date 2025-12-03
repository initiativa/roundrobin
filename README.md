# plugin roundrobin for GLPI

GLPI - Automatic Round Robin Assignment in ticket by selected category

This plugin allow to define a round robin policy to assign tickets to a group fo technicians. It permits to distribute the load of job among technicians grouped in single glpi group.
You just need to fulfill the field "Group in charge of the hardware" of the an ITIL category. Every time a ticket having such a category will be opened, the plugin will check the group and will assign the ticket to one of the member of the group continuing, the next ticket, with the other members.
The plugin adapt its behavior when the group or the members are changed.

While setting up the plugin you can decide the categories for which the plugin should work and if adding also the same group as assignee (useful to allow other technicians to manage the queue in case of absence for example).

## GLPI 11 Compatibility

Version 2.0.0 adds support for GLPI 11.x:
- Updated to use GLPI 11 actor system (`_actors` array) for ticket assignment
- Uses GLPI's DB framework methods instead of raw SQL queries
- Compatible with PHP 8.1+
- Uses Twig templates for configuration form

enjoy!
