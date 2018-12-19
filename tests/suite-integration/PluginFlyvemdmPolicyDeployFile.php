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

class PluginFlyvemdmPolicyDeployFile extends CommonTestCase {

   private $defaultEntity = 0;

   /**
    * @tags testApplyPolicy
    */
   public function testApplyPolicy() {
      // Create an application (directly in DB) because we are not uploading any file
      // Create an file (directly in DB)
      $file = $this->createDummyFile($this->defaultEntity);

      $policyDataDeploy = new \PluginFlyvemdmPolicy();
      $this->boolean($policyDataDeploy->getFromDBBySymbol('deployFile'))->isTrue();

      $fleet = $this->createFleet([
         'entities_id' => $this->defaultEntity,
         'name'        => __CLASS__ . '::'. __FUNCTION__,
      ]);

      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();

      // check failure if no value
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk            => $policyDataDeploy->getID(),
         'itemtype'           => get_class($file),
         'items_id'           => $file->getID(),
      ]);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no destination
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new \stdClass();
      $value->remove_on_delete = '1';

      $task = new \PluginFlyvemdmTask();
      $task->add([
         $fleetFk   => $fleet->getID(),
         $policyFk  => $policyDataDeploy->getID(),
         'itemtype' => get_class($file),
         'items_id' => $file->getID(),
         'value'    => json_encode($value, JSON_UNESCAPED_SLASHES),
      ]);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no remove on delete flag
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new \stdClass();
      $value->destination = "%SDCARD%/path/to/";

      $task = new \PluginFlyvemdmTask();
      $task->add([
         $fleetFk   => $fleet->getID(),
         $policyFk  => $policyDataDeploy->getID(),
         'itemtype' => get_class($file),
         'items_id' => $file->getID(),
         'value'    => json_encode($value, JSON_UNESCAPED_SLASHES),
      ]);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if not itemId
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new \stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk            => $policyDataDeploy->getID(),
         'itemtype'           => get_class($file),
         'items_id'           => $file->getID(),
         'value'              => json_encode($value, JSON_UNESCAPED_SLASHES),
      ]);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no itemtype
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new \stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk            => $policyDataDeploy->getID(),
         'items_id'           => $file->getID(),
         'value'              => json_encode($value, JSON_UNESCAPED_SLASHES),
      ]);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check add the policy to fleet with correct parameters succeeds
      $task = $this->applyAddFilePolicy($policyDataDeploy, $file, $fleet);
      $this->boolean($task->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check adding a deploy policy cannot be done twice
      $task = $this->applyAddFilePolicy($policyDataDeploy, $file, $fleet);
      $this->boolean($task->isNewItem())
         ->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check remove deployment policy
      $task = new \PluginFlyvemdmTask();
      $task->getFromDBForItems($fleet, $policyDataDeploy);

      $this->boolean($task->delete([
         'id' => $task->getID(),
      ]))->isTrue();
   }

   /**
    * @param \PluginFlyvemdmPolicy $policyData
    * @param \PluginFlyvemdmFile $file
    * @param \PluginFlyvemdmFleet $fleet
    *
    * @return \PluginFlyvemdmTask
    */
   private function applyAddFilePolicy(
      \PluginFlyvemdmPolicy $policyData,
      \PluginFlyvemdmFile $file,
      \PluginFlyvemdmFleet $fleet
   ) {
      $value = new \stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'            => $fleet->getType(),
         'items_id_applied'            => $fleet->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $value,
         'itemtype'                    => get_class($file),
         'items_id'                    => $file->getID(),
      ]);

      return $task;
   }
}
