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
class PluginFlyvemdmPolicy extends CommonDBTM {

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname = 'flyvemdm:policy';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad = false;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights = false;

   /**
    * Finds the symbol that matches the argument
    * @param string $symbol
    * @return boolean true if the symbol is found
    */
   public function getFromDBBySymbol($symbol) {
      return $this->getFromDBByCrit(['symbol' => $symbol]);
   }

   /**
    * @see CommonDBTM::post_getFromDB()
    */
   public function post_getFromDB() {
      // Translate some fields
      $this->fields['name'] = __($this->fields['name'], 'flyvemdm');
      $this->fields['comment'] = __($this->fields['comment'], 'flyvemdm');

      // Internationalize type_data field depending on the type of policy
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($this);
      $translatedTypeData = $policy->translateData();
      $this->fields['type_data'] = json_encode($translatedTypeData, JSON_UNESCAPED_SLASHES);
   }

   /**
    * Localized name of the type
    * @param integer $nb number of item in the type (default 0)
    * @return string
    */
   static function getTypeName($nb = 0) {
      return _n('Policy', 'Policies', $nb, 'flyvemdm');
   }

   /**
    * @see CommonDBTM::getSearchOptionsNew()
    * @return array
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      if (method_exists('CommonDBTM', 'rawSearchOptions')) {
         $tab = parent::rawSearchOptions();
      } else {
         $tab = parent::getSearchOptionsNew();
      }

      $tab[0] = [
         'id'   => 'common',
         'name' => __('Policy', 'flyvemdm'),
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'massiveaction' => false,
         'datatype'      => 'number',
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => PluginFlyvemdmPolicyCategory::getTable(),
         'field'         => 'completename',
         'name'          => __('Policy category', 'flyvemdm'),
         'datatype'      => 'dropdown',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'       => '4',
         'table'    => $this->getTable(),
         'field'    => 'type',
         'name'     => __('Type'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'type_data',
         'name'          => __('Enumeration data', 'flyvemdm'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'group',
         'name'          => __('Group'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'default_value',
         'name'          => __('Default value', 'flyvemdm'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => $this->getTable(),
         'field'         => 'recommended_value',
         'name'          => __('Recommended value', 'flyvemdm'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'is_android_system',
         'name'          => __('Requires system permission', 'flyvemdm'),
         'datatype'      => 'bool',
         'searchtype'    => ['equals'],
         'massiveaction' => false,
      ];

      $tab[] = [
        'id'            => '10',
        'table'         => $this->getTable(),
        'field'         => 'android_min_version',
        'name'          => __('Android minimum version', 'flyvemdm'),
        'datatype'      => 'string',
        'massiveaction' => false,
      ];

      $tab[] = [
        'id'            => '11',
        'table'         => $this->getTable(),
        'field'         => 'android_max_version',
        'name'          => __('Android maximum version', 'flyvemdm'),
        'datatype'      => 'string',
        'massiveaction' => false,
      ];

      $tab[] = [
        'id'            => '12',
        'table'         => $this->getTable(),
        'field'         => 'apple_min_version',
        'name'          => __('Apple minimum version', 'flyvemdm'),
        'datatype'      => 'string',
        'massiveaction' => false,
      ];

      $tab[] = [
        'id'            => '13',
        'table'         => $this->getTable(),
        'field'         => 'apple_max_version',
        'name'          => __('Apple maximum version', 'flyvemdm'),
        'datatype'      => 'string',
        'massiveaction' => false,
      ];

      return $tab;
   }

   public static function dropdown($options = []) {
      global $DB;

      // Startiing from GLPI 9.3.1 ability to do more complex requests
      // and simplify this code
      $request = [
         'FROM' => PluginFlyvemdmPolicyCategory::getTable(),
      ];

      $elements = $category = [];
      foreach ($DB->request($request) as $row) {
         $elements[$row['name']] = [];
         $category[$row['id']] = $row['completename'];
      }

      $request = [
         'FROM' => static::getTable(),
      ];
      foreach ($DB->request($request) as $row) {
         $categoryName = $category[$row['plugin_flyvemdm_policycategories_id']];
         $elements[$categoryName][$row['id']] = $row['name'];
      }

      $elements = array_filter($elements, function($value) { return (count($value) > 0); });
      return Dropdown::showFromArray(static::getForeignKeyField(), $elements, $options);
   }

}
