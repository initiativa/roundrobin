<?php

/**
 * -------------------------------------------------------------------------
 * RoundRobin plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of RoundRobin GLPI Plugin.
 *
 * RoundRobin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * RoundRobin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with RoundRobin. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 by initiativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/initiativa/roundrobin
 * -------------------------------------------------------------------------
 */

/**
 * Pure rotation helper (no GLPI dependency, unit-tested).
 */
class PluginRoundRobinRotation {

    /**
     * Pick the index of the next eligible member, keeping the rotation order of the full list.
     *
     * @param int|null $lastIndex        index assigned last time (null = never)
     * @param array    $members          full ordered member list, each row has 'UserId'
     * @param array    $eligibleUserIds  user ids allowed to receive the ticket
     *
     * @return int|null index into $members, or null when nobody is eligible
     */
    public static function pickNextIndex(?int $lastIndex, array $members, array $eligibleUserIds): ?int {
        $count = count($members);
        if ($count === 0) {
            return null;
        }
        $eligible = array_flip(array_map('intval', $eligibleUserIds));
        $start    = $lastIndex === null ? 0 : (($lastIndex % $count) + 1) % $count;

        for ($step = 0; $step < $count; $step++) {
            $idx = ($start + $step) % $count;
            if (isset($eligible[(int) $members[$idx]['UserId']])) {
                return $idx;
            }
        }
        return null;
    }
}
