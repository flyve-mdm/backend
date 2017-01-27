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

/**
 * Entry point for installation process
 */
function plugin_flyvemdm_install() {

   global $DB;

   require_once(PLUGIN_FLYVEMDM_ROOT . "/install/installer.class.php");
   $installer = new PluginFlyvemdmInstaller();

   return $installer->install();

}

/**
 * Uninstalls the plugin
 * @return boolean True if success
 */
function plugin_flyvemdm_uninstall() {

   require_once(PLUGIN_FLYVEMDM_ROOT . "/install/installer.class.php");
   $installer = new PluginFlyvemdmInstaller();

   return $installer->uninstall();

}

/**
 * Second pass of initialization after all other initiaization of other plugins
 * Also force inclusion of this file
 */
function plugin_flyvemdm_postinit() {

}

/**
 * Actions done when a profile_user is being purged
 */
function plugin_flyvemdm_hook_pre_profileuser_purge(CommonDBTM $item) {
   $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id', 'registered_profiles_id'));
   $guestProfileId = $config['guest_profiles_id'];

   if ($item->getField('profiles_id') == $guestProfileId) {
      $invitation = new PluginFlyvemdmInvitation();
      if (!$invitation->deleteByCriteria(array('users_id' => $item->getField('users_id')))) {
         $item->input = false;
      }
   }
}

/**
 * Define Dropdown tables to be managed in GLPI
 * @return translated[]
 */
function plugin_flyvemdm_getDropdown() {

   $plugin = new Plugin();
   if ($plugin->isActivated("flyvemdm")) {
      // Table => Name
      return array(
            'PluginFlyvemdmWellknownpath'  => __("Well known Android's FS paths", 'flyvemdm'),
            'PluginFlyvemdmPolicyCategory' => __("Policy Categories", 'flyvemdm')
      );
   } else {
      return array();
   }

}

/**
 * Add a default WHERE in SELECT queries on itemtypes
 * @param string $itemtype Itemtype
 */
function plugin_Flyvemdm_addDefaultJoin($itemtype) {

   switch ($itemtype) {
      case 'PluginFlyvemdmGeolocation':
         return PluginFlyvemdmGeolocation::addDefaultJoin();

      case 'PluginFlyvemdmAgent':
         return PluginFlyvemdmAgent::addDefaultJoin();
   }

}

/**
 * Add a default WHERE in SELECT queries on itemtypes
 * @param string $itemtype Itemtype
 */
function plugin_Flyvemdm_addDefaultWhere($itemtype) {

   switch ($itemtype) {
      case 'PluginFlyvemdmGeolocation':
         return PluginFlyvemdmGeolocation::addDefaultWhere();

      case 'PluginFlyvemdmAgent':
         return PluginFlyvemdmAgent::addDefaultWhere();
   }

}

function plugin_flyvemdm_getDatabaseRelations() {
   return [
   ];
}
