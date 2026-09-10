# plugin roundrobin for GLPI

GLPI - Automatic Round Robin Assignment in ticket by selected category

This plugin allow to define a round robin policy to assign tickets to a group fo technicians. It permits to distribute the load of job among technicians grouped in single glpi group.
You just need to fulfill the field "Group in charge of the hardware" of the an ITIL category. Every time a ticket having such a category will be opened, the plugin will check the group and will assign the ticket to one of the member of the group continuing, the next ticket, with the other members.
The plugin adapt its behavior when the group or the members are changed.

While setting up the plugin you can decide the categories for which the plugin should work and if adding also the same group as assignee (useful to allow other technicians to manage the queue in case of absence for example).

enjoy!

## Extending: filtering members from another plugin (since 1.1.0)

Other plugins can restrict which group members are eligible for assignment by registering a
callback on the `roundrobin_filter_members` hook:

```php
$PLUGIN_HOOKS['roundrobin_filter_members']['myplugin'] = 'plugin_myplugin_filter_members';

function plugin_myplugin_filter_members(array $params): array {
    // $params['members']: rows with 'UserId', 'Username', ... (full group, rotation order)
    // $params['itilcategories_id'], $params['groups_id']
    $params['members'] = array_values(array_filter(
        $params['members'],
        fn($m) => my_is_available((int) $m['UserId'])
    ));
    return $params;
}
```

The rotation index is kept over the full member list and skips ineligible members, so an
absent technician does not shift the turn of the others and gets their turn back when they
return. When nobody is eligible the ticket is left without a technician (it stays "New").
A callback that throws is logged and ignored, so it never blocks ticket creation.

Used by the [Presence](https://github.com/Facil-Group/glpi-presence) plugin (Teams-like
presence status: only "Available" technicians receive tickets).

### Development

```
php phpunit.phar        # or: composer install && composer test
```
