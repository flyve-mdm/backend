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

class PluginFlyvemdmPolicyStringTest extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
      self::login('glpi', 'glpi', true);
   }

   public function testCreatePolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
            'group'     => 'testGroup',
            'symbol'    => 'stringPolicy',
            'type_data' => '',
            'unicity'   => '1',
      ];
      $policy = new PluginFlyvemdmPolicyString($policyData);
      $this->assertInstanceOf('PluginFlyvemdmPolicyString', $policy);
      return $policy;
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testIntegrityCheckWithString(PluginFlyvemdmPolicyInterface $policy) {
      $this->assertTrue($policy->integrityCheck('a little string', null, '0'));
   }


   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testApplyPolicy(PluginFlyvemdmPolicyInterface $policy) {
      $array = $policy->getMqttMessage('a little string', null, '0');
      reset($array);
      $symbol = key($array);
      $this->assertAttributeEquals('stringPolicy', 'symbol', $policy);
   }

}