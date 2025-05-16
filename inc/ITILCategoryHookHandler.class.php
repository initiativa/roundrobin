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
class PluginRoundRobinITILCategoryHookHandler extends CommonDBTM implements IPluginRoundRobinHookItemHandler {

    public function itemAdded(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        $rrAssignmentsEntity->insertItilCategory($this->getItilCategoryId($item));
    }

    protected function getItilCategoryId(CommonDBTM $item) {
        return $item->fields['id'];
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        $rrAssignmentsEntity->updateIsActive($this->getItilCategoryId($item), 0);
    }

    public function itemPurged(CommonDBTM $item) {
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginRoundRobinLogger::addDebug(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        $rrAssignmentsEntity->deleteItilCategory($this->getItilCategoryId($item));
    }

}
