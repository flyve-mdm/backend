<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
 */

class PluginFlyvemdmFileIntegrationTest extends RegisteredUserTestCase
{

   protected $fileDestination;

   public function setUp() {
      parent::setUp();
      $this->fileDestination = '%SDCARD%/path/to/';
   }

   public function testInitAddFleet() {
      $fleet = new PluginFlyvemdmFleet();
      $fleet->add([
            'entities_id'     => $_SESSION['glpiactive_entity'],
            'name'            => 'a fleet'
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testInitCreateFile() {
      global $DB;

      // Create an file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginFlyvemdmFile::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`
      )
      VALUES (
         '$fileName',
         '2/12345678_flyve-user-manual.pdf',
         '$entityId'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = new PluginFlyvemdmFile();
      $this->assertTrue($file->getFromDBByQuery("WHERE `name`='$fileName'"), $mysqlError);

      return $file;
   }

   public function testGetFileDeploymentPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployFile'));

      return $policyData;
   }

   public function testGetFileRemovalPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('removeFile'));

      return $policyData;
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitAddFleet
    */
   public function testApplyPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $fleet_policy = $this->ApplyAddFilePolicy($policyData, $file, $fleet);
      $this->assertFalse($fleet_policy->isNewItem());

      return $fleet_policy;
   }

   /**
    * @depends testInitCreateFile
    */
   public function testDeleteFile(PluginFlyvemdmFile $file) {
      $this->assertTrue($file->delete([
            'id'           => $file->getID()
      ]));

      return $file;
   }

   /**
    * @depends testInitCreateFile
    * @depends testApplyPolicy
    * @depends testDeleteFile
    */
   public function testAppliedPoliciesRemoved(PluginFlyvemdmFile $file, PluginFlyvemdmFleet_Policy $fleet_policy) {
      $itemtype = $file->getType();
      $itemId = $file->getID();
      $rows = $fleet_policy->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->assertEquals(0, count($rows));
   }

   /**
    * @depends testGetFileRemovalPolicy
    * @depends testInitCreateFile
    * @depends testApplyPolicy
    * @depends testDeleteFile
    */
   public function testRemovePolicyAdded(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet_Policy $fleet_policy) {
      $policyId = $policyData->getID();
      $filePath = $this->fileDestination . $file->getField('name');
      $rows = $fleet_policy->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$filePath'");
      $this->assertEquals(1, count($rows));
   }

   /**
    *
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitAddFleet
    * @depends testRemovePolicyAdded
    */
   public function testAddAndRemoveConflict(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $fleet_policy = $this->ApplyAddFilePolicy($policyData, $file, $fleet);
      $this->assertTrue($fleet_policy->isNewItem());
   }

   protected function ApplyAddFilePolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $this->fileDestination;

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID()
      ]);

      return $fleet_policy;
   }
}
