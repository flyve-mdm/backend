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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}
/**
 * @since 0.1.0.33
 */
class PluginStorkmdmPolicyInteger extends PluginStorkmdmPolicyBase implements PluginStorkmdmPolicyInterface {

   /**
    * @var integer|null $minValue Minimum value allowed for the policy
    */
   protected $minValue = null;
   /**
    * @var integer|null $minValue Maximum value allowed for the policy
    */
   protected $maxValue = null;

   /**
    * @param string $properties JSON encoded properties
    * @param string $symbol name of the policy used for MQTT messages
    */
   public function __construct(PluginStorkmdmPolicy $policy) {
      parent::__construct($policy);
      $defaultProperties = [
            'min' => null,
            'max' => null
      ];
      $propertyCollection = $this->jsonDecodeProperties($policy->getField('type_data'), $defaultProperties);
      $this->minValue = $propertyCollection['min'];
      $this->maxValue = $propertyCollection['max'];

      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyInterface::integrityCheck()
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      if ($value === null || (!strval(intval($value)) == strval($value))) {
         return false;
      }

      if ($this->minValue !== null && ($value < $this->minValue)) {
         return false;
      }

      if ($this->maxValue !== null && ($value > $this->maxValue)) {
         return false;
      }
      return true;

   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyInterface::jsonEncode()
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      if (!$this->integrityCheck($value, $itemtype, $itemId)) {
         return false;
      }
      $array = [
            $this->symbol => $value
      ];
      return $array;
   }

}