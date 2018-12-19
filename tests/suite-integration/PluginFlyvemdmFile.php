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

class PluginFlyvemdmFile extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      $this->terminateSession();
   }

   /**
    * @tags testApplyPolicy
    */
   public function testApplyPolicy() {
      $file = $this->createDummyFile($_SESSION['glpiactive_entity']);
      $fileDestination = '%SDCARD%/path/to/';

      // Apply a policy on a file
      $deployPolicyData = $this->getFileDeploymentPolicy();
      $fleet = $this->createFleet([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name'        => __CLASS__ . '::'. __FUNCTION__,
      ]);

      $task = $this->ApplyAddFilePolicy($deployPolicyData, $file, $fleet, $fileDestination);
      $this->boolean($task->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // delete the file
      $this->boolean($file->delete([
         'id' => $file->getID(),
      ]))->isTrue();

      // Test a file removal policy is created with expected content
      $removePolicyData = $this->getFileRemovalPolicy();
      $policyId = $removePolicyData->getID();
      $filePath = $fileDestination . $file->getField('name');
      $rows = $task->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$filePath'");
      $this->integer(count($rows))->isEqualTo(1);

      // Test the applied policies are removed
      $itemtype = $file->getType();
      $itemId = $file->getID();
      $rows = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->integer(count($rows))->isEqualTo(0);

      // Test add policy fails when a removal policy exists
      $fleet_policy = $this->ApplyAddFilePolicy($deployPolicyData, $file, $fleet, $fileDestination);
      $this->boolean($fleet_policy->isNewItem())->isTrue();
   }

   /**
    * @return \PluginFlyvemdmPolicy
    */
   private function getFileDeploymentPolicy() {
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('deployFile'))->isTrue();

      return $policyData;
   }

   /**
    * @return \PluginFlyvemdmPolicy
    */
   private function getFileRemovalPolicy() {
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('removeFile'))->isTrue();

      return $policyData;
   }

   /**
    * @param \PluginFlyvemdmPolicy $policyData
    * @param \PluginFlyvemdmFile $file
    * @param \PluginFlyvemdmFleet $fleet
    * @param $filedestination
    * @return \PluginFlyvemdmTask
    */
   private function ApplyAddFilePolicy(
      \PluginFlyvemdmPolicy $policyData,
      \PluginFlyvemdmFile $file,
      \PluginFlyvemdmFleet $fleet,
      $filedestination
   ) {
      $value = new \stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $filedestination;

      $task = new \PluginFlyvemdmTask();
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk            => $policyData->getID(),
         'value'              => $value,
         'itemtype'           => \PluginFlyvemdmFile::class,
         'items_id'           => $file->getID(),
      ]);

      return $task;
   }

}
