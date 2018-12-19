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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}
/**
 * @since 0.1.33
 */
class PluginFlyvemdmPolicyBoolean extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * PluginFlyvemdmPolicyBoolean constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return bool
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // no item link allowed
      if ($itemtype !== null && $itemtype != '') {
         return false;
      }
      if ($itemId != 0) {
         return false;
      }
      return ($value == '0' || $value == '1');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return array|bool
    */
   public function getBrokerMessage($value, $itemtype, $itemId) {
      if (!$this->integrityCheck($value, $itemtype, $itemId)) {
         return false;
      }
      $value = $value == '0' ? 'false' : 'true';
      $array = [
         $this->symbol => $value
      ];
      return $array;
   }

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      $data['itemtype'] = $itemType;
      $data['typeTmpl'] = PluginFlyvemdmPolicyBoolean::class;
      $data['dropdown'] = [
          Dropdown::showYesNo('value', $value, -1, ['display' => false])
      ];
      $data['android_requirements'] = $this->getAndroidCompatibilityMessage();
      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_value.html.twig', ['data' => $data]);
   }

   /**
    * @param PluginFlyvemdmTask $task
    * @return string
    */
   public function showValue(PluginFlyvemdmTask $task) {
      $value = $task->getField('value');
      if ($value == '0') {
         return __('No');
      }
      return __('Yes');
   }
}
