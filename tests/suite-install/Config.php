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
use Plugin;

/**
 * Engine inline required to execute tests of this class
 * @engine inline
 */
class Config extends CommonTestCase {
   private $olddb;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      switch ($method) {
         case 'testInstallPlugin':
            $this->login('glpi', 'glpi');
            break;

         case 'testUpgradePlugin':
            $this->olddb = new \DB();
            $this->string(getenv('OLD_DB_NAME'));
            $this->olddb->dbdefault = getenv('OLD_DB_NAME');
            $this->olddb->connect();
            $this->boolean($this->olddb->connected)->isTrue();
            break;
      }
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      switch ($method) {
         case 'testUpgradePlugin':
            $this->olddb->close();
            break;
      }
   }

   /**
    * @tags testInstallPlugin
    */
   public function testInstallPlugin() {
      global $DB;

      $pluginName = TEST_PLUGIN_NAME;

      $this->given($this->boolean($DB->connected)->isTrue())
         ->and($this->configureGLPI())
         ->and($this->installDependencies());

      //Drop plugin configuration if exists
      $config = $this->newTestedInstance();
      $config->deleteByCriteria(['context' => $pluginName]);

      // Drop tables of the plugin if they exist
      $query = "SHOW TABLES";
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {
         if (strstr($data[0], "glpi_plugin_$pluginName") !== false) {
            $DB->query("DROP TABLE " . $data[0]);
         }
      }

      // Reset logs
      $this->resetGLPILogs();

      $plugin = new Plugin();
      $plugin->getFromDBbyDir($pluginName);

      // Install the plugin
      $log = '';
      ob_start(function ($in) use ($log) {
         $log .= $in;
         return '';
      });
      $plugin->install($plugin->fields['id']);
      ob_end_clean();
      $this->boolean($plugin->isInstalled($pluginName))->isTrue($log);

      // Enable the plugin
      $plugin->activate($plugin->fields['id']);
      $this->boolean($plugin->isActivated($pluginName))->isTrue('Cannot enable the plugin');

      // Check version and schema version are in the configuration
      $config = \Config::getConfigurationValues(
         $pluginName, [
            'version',
            'schema_version'
         ]
      );
      $this->string($config['version']);
      $this->string($config['schema_version']);

      // Enable debug mode for enrollment messages
      \Config::setConfigurationValues($pluginName, ['debug_enrolment' => '1']);

      // Configure MQTT broker
      \Config::setConfigurationValues($pluginName, [
         'mqtt_broker_address' => '127.0.0.1',
         'mqtt_broker_internal_address' => '127.0.0.1',
      ]);

      // Test there is an initial default fleet
      $fleet = new \PluginFlyvemdmFleet();
      $dbUtils = new \DBUtils();
      $count = $dbUtils->countElementsInTable($fleet::getTable());
      $this->integer($count)->isEqualTo(1);
      $fleet->getFromDB(1);
      $this->boolean($fleet->isNewItem())->isFalse();
      $this->integer((int) $fleet->isRecursive())->isEqualTo(0);

      $config = \Config::getConfigurationValues('flyvemdm');

      // Test an agent's user profile exists
      $this->integer((int) $config['agent_profiles_id'])->isGreaterThan(0);
      $agentProfileId = $config['agent_profiles_id'];
      $profile = new \Profile();
      $profile->getFromDB($agentProfileId);
      $this->boolean($profile->isNewItem())->isFalse();
      $this->string($profile->getField('name'))->isEqualTo('Flyve MDM device agent users');

      // Test policies are populated
      $policy = new \PluginFlyvemdmPolicy();
      $count = $dbUtils->countElementsInTable($policy::getTable());
      $this->integer($count)->isEqualTo(count(CommonTestCase::policyList()));

      // Take a snapshot of the database before any test
      $this->mysql_dump($DB->dbuser, $DB->dbhost, $DB->dbpassword, $DB->dbdefault, './save.sql');

      $this->boolean(file_exists("./save.sql"))->isTrue();
      $filestats = stat("./save.sql");
      $length = $filestats[7];
      $this->integer($length)->isGreaterThan(0);
   }

   /**
    * @tags testUpgradePlugin
    */
   public function testUpgradePlugin() {
      global $DB;

      $pluginName = TEST_PLUGIN_NAME;

      $result = $DB->listTables("glpi_plugin_${pluginName}_%");
      while ($fresh_table = $result->next()) {
         $table = $fresh_table['TABLE_NAME'];
         $this->boolean($this->olddb->tableExists($table, false))->isTrue("Table $table does not exists from migration!");

         $create = $DB->getTableSchema($DB, $table);
         $fresh = $create['schema'];
         $fresh_idx = $create['index'];

         $update = $DB->getTableSchema($this->olddb, $table);
         $updated = $update['schema'];
         $updated_idx = $update['index'];

         //compare table schema
         $this->string($updated)->isIdenticalTo($fresh);
         //check index
         $fresh_diff = array_diff($fresh_idx, $updated_idx);
         $this->array($fresh_diff)->isEmpty("Index missing in update for $table: " . implode(', ', $fresh_diff));
         $update_diff = array_diff($updated_idx, $fresh_idx);
         $this->array($update_diff)->isEmpty("Index missing in empty for $table: " . implode(', ', $update_diff));
      }
   }

   /**
    * Configure GLPI to install the plugin
    */
   private function configureGLPI() {
      global $CFG_GLPI;

      $settings = [
         'use_notifications'               => '1',
         'notifications_mailing'           => '1',
         'enable_api'                      => '1',
         'enable_api_login_credentials'    => '1',
         'enable_api_login_external_token' => '1',
      ];
      \Config::setConfigurationValues('core', $settings);

      $CFG_GLPI = $settings + $CFG_GLPI;

      $settings = [
         'mqtt_broker_port' => '1884',
      ];
      \Config::setConfigurationValues('flyvemdm', $settings);
   }

   /**
    * install requirements for the plugin
    */
   private function installDependencies() {
      $rule = new \Rule();
      $this->boolean($rule->getFromDBByCrit([
         'sub_type' => 'PluginFusioninventoryInventoryRuleImport',
         'name'     => 'Computer constraint (name)',
      ]))->isTrue();
      $this->boolean($rule->update(['id' => $rule->getID(), 'is_active' => 0]))->isTrue();

      $rule = new \Rule();
      $this->boolean($rule->getFromDBByCrit([
         'sub_type' => 'PluginFusioninventoryInventoryRuleImport',
         'name'     => 'Computer update (by name)',
      ]))->isTrue();
      $this->boolean($rule->update(['id' => $rule->getID(), 'is_active' => 0]))->isTrue();

      $rule = new \Rule();
      $this->boolean($rule->getFromDBByCrit([
         'sub_type' => 'PluginFusioninventoryInventoryRuleImport',
         'name'     => 'Computer import (by name)',
      ]))->isTrue();
      $this->boolean($rule->update(['id' => $rule->getID(), 'is_active' => 0]))->isTrue();
   }
}
