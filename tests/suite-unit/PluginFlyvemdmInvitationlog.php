<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmInvitationlog extends CommonTestCase {

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
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:invitationLog');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->createInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Invitation log')
         ->string($instance->getTypeName(3))->isEqualTo('Invitation logs');
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->createInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Create',
         'Read',
         'Update',
         ['short' => 'Purge', 'long' => 'Delete permanently'],
      ]);
   }

   /**
    * @tags testGetSearchOptionsNew
    */
   public function testGetSearchOptionsNew() {
      $this->given($this->newTestedInstance)
         ->array($result = $this->testedInstance->getSearchOptionsNew())
         ->child[0](function ($child) {
            $child->hasKeys(['id', 'name'])->values
               ->string[0]->isEqualTo('common')
               ->string[1]->isEqualTo('Invitation log');
         })
         ->child[1](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitationlogs')
               ->string[2]->isEqualTo('id')
               ->string[5]->isEqualTo('number');
         })
         ->child[2](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitationlogs')
               ->string[2]->isEqualTo('date_creation')
               ->string[5]->isEqualTo('datetime');
         })
         ->child[3](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitations')
               ->string[2]->isEqualTo('id')
               ->string[5]->isEqualTo('dropdown');
         })
         ->child[4](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitationlogs')
               ->string[2]->isEqualTo('event')
               ->string[5]->isEqualTo('string');
         });
   }
}