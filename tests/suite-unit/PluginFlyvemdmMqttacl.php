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

class PluginFlyvemdmMqttacl extends CommonTestCase {

   /**
    * @return object
    */
   private function createInstance() {
      $this->newTestedInstance();
      return $this->testedInstance;
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $this->testedClass->hasConstant('MQTTACL_NONE');
      $this->testedClass->hasConstant('MQTTACL_READ');
      $this->testedClass->hasConstant('MQTTACL_WRITE');
      $this->testedClass->hasConstant('MQTTACL_READ_WRITE');
      $this->testedClass->hasConstant('MQTTACL_ALL');
   }

   /**
    * @tags testRemoveAllForUser
    */
   public function testRemoveAllForUser() {
      $instance = $this->createInstance();
      $mockedClass = $this->newMockInstance('\PluginFlyvemdmMQTTUser');
      $mockedClass->getMockController()->getID = 2;
      $this->boolean($instance->removeAllForUser($mockedClass))
         ->isTrue(); // probably a bug? the user doesn't exist
   }

   /**
    * @tags testPrepareInputForAdd
    */
   public function testPrepareInputForAdd() {
      $instance = $this->createInstance();
      $this->array($result = $instance->prepareInputForAdd(['access_level' => 1]))
         ->integer($result['access_level'])->isEqualTo(1);

      $this->array($result = $instance->prepareInputForAdd(['access_level' => 4]))
         ->integer($result['access_level'])->isEqualTo(0);
   }
}