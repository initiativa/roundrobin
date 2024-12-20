<?php

/**
 * -------------------------------------------------------------------------
 * TicketBalance plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of TicketBalance GLPI Plugin.
 *
 * TicketBalance is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * TicketBalance is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TicketBalance. If not, see <http://www.gnu.org/licenses/>
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 - https://www.linkedin.com/in/richard-ti/
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/RPGMais/ticketbalance
 * -------------------------------------------------------------------------
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

interface IPluginRoundRobinHookItemHandler {

    public function itemAdded(CommonDBTM $item);

    public function itemDeleted(CommonDBTM $item);

    public function itemPurged(CommonDBTM $item);
}
