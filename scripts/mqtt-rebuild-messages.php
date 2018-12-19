<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include (__DIR__ . "/../vendor/docopt/docopt/src/docopt.php");

$doc = <<<DOC
mqtt-rebuild-messages.php

Usage:
   mqtt-rebuild-messages.php [ --tests ]

Options:
   --tests              Use GLPI test database
   --debug              Verbose mode for debug (dumps all messages)

DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);
if (isset($args['--tests']) && $args['--tests'] !== false) {
   echo "running in testing environment" . PHP_EOL;
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
   define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
}

include (__DIR__ . '/../../../inc/includes.php');

$fleetFk = PluginFlyvemdmFleet::getForeignKeyField();
$request = [
   'SELECT' => ['id'],
   'FROM' => PluginFlyvemdmFleet::getTable(),
   'WHERE' => ['is_default' => ['<>' => '0']]
];
$fleet = new PluginFlyvemdmFleet();
foreach ($DB->request($request) as $row) {
   $fleet->getFromDB($row['id']);
   $fleet->refreshPersistedNotifications();
}
