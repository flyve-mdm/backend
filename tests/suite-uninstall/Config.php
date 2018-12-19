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

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class Config extends CommonTestCase
{

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
   }

   /**
    * @tags testUninstallPlugin
    * @engine inline
    */
   public function testUninstallPlugin() {
      global $DB;

      $pluginName = TEST_PLUGIN_NAME;

      $plugin = new \Plugin();
      $plugin->getFromDBbyDir($pluginName);

      // Uninstall the plugin
      $log = '';
      ob_start(function($in) use ($log) {
         $log .= $in;
         return '';
      });
      $plugin->uninstall($plugin->getID());
      ob_end_clean();
      $this->boolean($plugin->isInstalled($pluginName))->isFalse($log);

      // Check the plugin is not installed
      $this->boolean($plugin->isInstalled($pluginName))->isFalse();

      // Check all plugin's tables are dropped
      $tables = [];
      $result = $DB->query("SHOW TABLES LIKE 'glpi_plugin_" . $pluginName . "_%'");
      while ($row = $DB->fetch_assoc($result)) {
         $tables[] = array_pop($row);
      }
      $this->integer(count($tables))->isEqualTo(0, "not deleted tables \n" . json_encode($tables, JSON_PRETTY_PRINT));

      // TODO: need to find a r eliable way to detect not clenaed
      // - NotificationTemplateTranslation
      // - Notification_NotificationTemplate

   }

}
