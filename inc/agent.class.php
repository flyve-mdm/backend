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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmAgent extends CommonDBTM implements PluginFlyvemdmNotifiable {

   const ENROLL_DENY             = 0;
   const ENROLL_INVITATION_TOKEN = 1;
   const ENROLL_ENTITY_TOKEN     = 2;

   const DEFAULT_TOKEN_LIFETIME  = "P7D";

   /**
    * @var string $rightname name of the right in DB
    */
   public static $rightname            = 'flyvemdm:agent';

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory                   = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = true;

   protected $topic = null;

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('Agent', 'Agents', $nb, "flyvemdm");
   }

   /**
    * Return the picture file for the menu
    * @return string
    */
   public static function getMenuPicture() {
      return '../pics/picto-agent.png';
   }

   /**
    * @since version 0.1.0
    * @see commonDBTM::getRights()
     */
   public function getRights($interface = 'central') {
      $rights = parent::getRights();
      /// For additional rights if needed
      //$rights[self::RIGHTS] = self::getTypeName();

      return $rights;
   }

   /**
    * Define tabs available for this itemtype
    * @see CommonGLPI::defineTabs()
    */
   public function defineTabs($options = array()) {
      //  TODO : fluent interface in GLPI 9.2 + when GLPI 9.1 dropped
      $tab = array();
      $this->addDefaultFormTab($tab);
      $this->addStandardTab('PluginFlyvemdmGeolocation', $tab, $options);
      $this->addStandardTab(__CLASS__, $tab, $options);
      $this->addStandardTab('Notepad', $tab, $options);
      $this->addStandardTab('Log', $tab, $options);

      return $tab;
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @since version 9.1
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $tab = array(1 => __('Danger zone !', 'flyvemdm'));
               return $tab;
               break;

            case PluginFlyvemdmFleet::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $fleetId = $item->getID();
                  $pluralNumber = Session::getPluralNumber();
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = countElementsInTable(static::getTable(), ['plugin_flyvemdm_fleets_id' => $fleetId]);
                  }
                  return self::createTabEntry(self::getTypeName($pluralNumber), $nb);
               }
               break;
         }
      }
   }

   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
    *
    * @since version 9.1
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch (get_class($item)) {
         case static::class:
            self::showDangerZone($item);
            return true;
            break;

         case PluginFlyvemdmFleet::class:
            self::showForFleet($item);
            return true;
            break;
      }
   }

   /**
    * Show form for edition
    */
   public function showForm($ID, $options = array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $canUpdate = (!$this->isNewID($ID)) && ($this->canUpdate() > 0);

      $fields              = $this->fields;
      $objectName          = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && $options['withtemplate'] == 2),
                             $this->getType(), -1);
      $fields['name']      = Html::autocompletionTextField($this, 'name',
                             array('value' => $objectName, 'display' => false));
      $fields['computer']  = Computer::dropdown([
                                 'display'      => false,
                                 'name'         => 'computers_id',
                                 'value'        => $this->fields['computers_id'],
                                 'entity'       => $this->fields['entities_id']
                             ]);
      $fields['fleet']     = PluginFlyvemdmFleet::dropdown([
                                    'display'      => false,
                                    'name'         => 'plugin_flyvemdm_fleets_id',
                                    'value'        => $this->fields['plugin_flyvemdm_fleets_id'],
                                    'entity'       => $this->fields['entities_id']
                             ]);
      if (empty($fields['last_contact'])) {
         $fields['last_contact'] = __('Never seen online', 'flyvemdm');
      }
      $data = [
            'withTemplate'    => (isset($options['withtemplate']) && $options['withtemplate'] ? '*' : ''),
            'isNewID'         => $this->isNewID($ID),
            'canUpdate'       => $canUpdate,
            'agent'           => $fields,
            'pingButton'      => Html::submit(_x('button', 'Ping'), array('name' => 'ping')),
            'geolocateButton' => Html::submit(_x('button', 'Geolocate'), array('name' => 'geolocate')),
            'inventoryButton' => Html::submit(_x('button', 'Inventory'), array('name' => 'inventory')),

      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('agent.html', $data);

      $this->showFormButtons($options);
   }

   /**
    * Print the computer's operating system form
    *
    * @param $comp Computer object
    *
    * @since version 9.1
    *
    * @return Nothing (call to classes members)
    */
   public static function showDangerZone(PluginFlyvemdmAgent $item) {
      global $CFG_GLPI, $DB;

      $ID = $item->fields['id'];
      $item->initForm($ID);
      $item->showFormHeader(['formtitle' => false]);
      $canedit = static::canUpdate();

      $fields              = $item->fields;

      $fields['lock']      = Html::getCheckbox([
            'title'        => __('Lock the device as soon as possible', 'flyvemdm'),
            'name'         => 'lock',
            'checked'      => $item->fields['lock'],
            'value'        => '1',
            'readonly'     => ($canedit == '0' ? '1' : '0'),
      ]);
      $fields['wipe']      = Html::getCheckbox([
            'title'        => __('Wipe the device as soon as possible', 'flyvemdm'),
            'name'         => 'wipe',
            'checked'      => $item->fields['wipe'],
            'value'        => '1',
            'readonly'     => ($canedit == '0' ? '1' : '0'),
      ]);

      $data = [
            'withTemplate'    => '',
            'isNewID'         => $item->isNewID($ID),
            'canUpdate'       => (!$item->isNewID($ID)) && ($item->canUpdate() > 0),
            'agent'           => $fields,
            'unenrollButton'  => Html::submit(_x('button', 'Unenroll'), array('name' => 'unenroll')),
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('agent_dangerzone.html', $data);

      $item->showFormButtons(array('candel' => false, 'formfooter' => false));
   }

   public static function showForFleet(PluginFlyvemdmFleet $item) {
      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      $SEARCHOPTION = Search::getOptions($itemtype);
      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      // Total Number of agents
      $number = countElementsInTableForMyEntities(static::getTable(), ['plugin_flyvemdm_fleets_id' => $items_id ]);

      // get the pager
      $pager = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);
      $pager = ''; // disabled because the results are not paged yet

      // get items
      $condition = "`plugin_flyvemdm_fleets_id` = '$items_id' " . getEntitiesRestrictRequest();
      $limit = $_SESSION['glpilist_limit'];
      $agent = new static();
      $rows = $agent->find($condition, '', '');

      $data = [
            'number' => $number,
            'pager'  => $pager,
            'agents' => $rows,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('agent_fleet.html', $data);

   }

   /**
    * @see CommonDBTM::canViewItem()
    */
   public function canViewItem() {
      // Check the active profile
      $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id'));
      if ($_SESSION['glpiactiveprofile']['id'] != $config['guest_profiles_id']) {
         return parent::canViewItem();
      }

      if (!$this->checkEntity(true)) {
         return false;
      }

      // the active profile is guest user, then check the user is
      // owner of the item's computer
      $computer = $this->getComputer();
      if ($computer === null) {
         return false;
      }

      return $_SESSION['glpiID'] == $computer->getField('users_id');
   }

   /**
    * Send a wipe command to the agent
    */
   protected function sendWipeQuery() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $mqttMessage = ['wipe' => 'now'];
         $this->notify("$topic/Command/Wipe", json_encode($mqttMessage, JSON_UNESCAPED_SLASHES), 0, 1);
      }
   }

   /**
    * Send a lock command to the agent
    */
   protected function sendLockQuery() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $mqttMessage = ['lock' => 'now'];
         $this->notify("$topic/Command/Lock", json_encode($mqttMessage, JSON_UNESCAPED_SLASHES), 0, 1);
      }
   }

   /**
    * Send unenrollment command to  the agent
    */
   protected function sendUnenrollQuery() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $mqttMessage = ['unenroll' => 'now'];
         $this->notify("$topic/Command/Unenroll", json_encode($mqttMessage, JSON_UNESCAPED_SLASHES), 0, 1);
      }
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      $config        = Config::getConfigurationValues("flyvemdm", array('debug_enrolment', 'mqtt_broker_address', 'mqtt_broker_port', 'mqtt_broker_tls'));

      // Get the maximum quantity of devices allowed for the current entity
      $entityConfig = new PluginFlyvemdmEntityconfig();
      if (!$entityConfig->getFromDBOrCreate($_SESSION['glpiactive_entity'])) {
         $this->filterMessages(Session::addMessageAfterRedirect(__('Failed to read configuration of the entity', 'flyvemdm')));
         return false;
      }

      $maxAgents = $entityConfig->getField('device_limit');
      $entityId = $_SESSION['glpiactive_entity'];
      $deviceCount = countElementsInTable($this->getTable(), "`entities_id`='$entityId'");
      if ($maxAgents > 0 && $deviceCount >= $maxAgents) {
         // Too many devices
         $this->filterMessages(Session::addMessageAfterRedirect(__('Too many devices', 'flyvemdm')));
         $input = false;
      }

      // User already logged in : user token has been validated

      switch ($this->chooseEnrollMethod($input)) {
         case self::ENROLL_DENY:
            $this->filterMessages(Session::addMessageAfterRedirect(__('Unable to find a enrollment method', 'flyvemdm')));
            $input = false;
            break;

         case self::ENROLL_INVITATION_TOKEN:
            $input = $this->enrollByInvitationToken($input);
            break;

         case self::ENROLL_ENTITY_TOKEN:
            // Method disabled, waiting for implementation
            $input = false;
            break;
      }

      unset($input['is_online']);

      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (isset($input['plugin_flyvemdm_fleets_id'])) {
         // Update MQTT ACL for the fleet
         $oldFleet = new PluginFlyvemdmFleet();
         if (!$oldFleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
            // Unable to load fleet currently associated to  the agent
            Session::addMessageAfterRedirect(__("The fleet of the device does not longer exists", 'flyvemdm'));
            return false;
         }

         $newFleet = new PluginFlyvemdmFleet();
         if (!$newFleet->getFromDB($input['plugin_flyvemdm_fleets_id'])) {
            //Unable to load the new fleet
            Session::addMessageAfterRedirect(__("The target fleet does not exists", 'flyvemdm'));
            return false;
         }

         $this->changeFleet($oldFleet, $newFleet);
      }

      // send wipe to the agent
      if (isset($input['wipe']) && $input['wipe'] != '0') {
         $input['wipe'] == '1';
      }

      // send lock to the agent
      if (isset($input['lock']) && $input['lock'] != '0') {
         $input['lock'] == '1';
      }

      unset($input['enroll_status']);
      if (isset($input['_unenroll'])) {
         $input['enroll_status'] = 'unenrolling';
      }

      //Send a connection status request to the device
      if (isset($input['_ping'])) {
         if ($this->getTopic() === null) {
            Session::addMessageAfterRedirect(__("The device is not enrolled yet", 'flyvemdm'));
            return false;
         }

         if (!$this->sendPingQuery()) {
            Session::addMessageAfterRedirect(__("Timeout querying the device", 'flyvemdm'));
            return false;
         }
      }

      if (isset($input['_geolocate'])) {
         if ($this->getTopic() === null) {
            Session::addMessageAfterRedirect(__("The device is not enrolled yet", 'flyvemdm'));
            return false;
         }

         $errorMessage = '';
         if (!$this->sendGeolocationQuery($errorMessage)) {
            Session::addMessageAfterRedirect($errorMessage);
            return false;
         }
      }

      if (isset($input['_inventory'])) {
         if ($this->getTopic() === null) {
            Session::addMessageAfterRedirect(__("The device is not enrolled yet", 'flyvemdm'));
            return false;
         }

         if (!$this->sendInventoryQuery()) {
            Session::addMessageAfterRedirect(__("Timeout querying the device inventory", 'flyvemdm'));
            return false;
         }
      }

      return $input;
   }

   /**
    * @see CommonDBTM::post_addItem()
    */
   public function post_addItem() {
      // Notify the agent about its fleets
      $this->updateSubscription();
   }

   /**
    * @see CommonDBTM::post_getFromDB()
    */
   public function post_getFromDB() {
      // set Topic after getting an item
      // Useful for post_purgeItem
      $this->getTopic();
      $this->setupMqttAccess();
      $this->fields['api_token'] = User::getToken($this->fields['users_id'], 'api_token');
   }

   /**
    * Actions done before the DELETE of the item in the database /
    * Maybe used to add another check for deletion
    *
    * @return bool : true if item need to be deleted else false
    */
   public function pre_deleteItem() {
      $success = false;

      // get serial of the computer
      $computer = $this->getComputer();
      if ($computer === null) {
         // The associated computer is already deleted
         return true;
      }

      // get the guest profile ID
      $config = Config::getConfigurationValues("flyvemdm", array('guest_profiles_id'));
      $guestProfileId = $config['guest_profiles_id'];
      if ($guestProfileId === null) {
         Session::addMessageAfterRedirect(__('Failed to find the guest user profile', 'flyvemdm'));
         return false;
      }

      $computerId = $computer->getID();
      $serial = $computer->getField('serial');
      $entityId = $this->getField('entities_id');
      $ownerUserId = $computer->getField('users_id');

      // Find other computers belong to the user in the current entity
      // TODO : maybe use getEntityRestrict for multientity support
      $rows = $computer->find("`entities_id`='$entityId' AND `users_id`='$ownerUserId' AND `id` <> '$computerId'", '', '1');
      if (count($rows) == 0) {
         // Remove guest habilitation for the entity
         $profile_User = new Profile_User();
         $success = $profile_User->deleteByCriteria([
               'users_id'        => $ownerUserId,
               'entities_id'     => $entityId,
               'profiles_id'     => $guestProfileId,
               'is_dynamic'      => 0
         ]);
         if (!$success) {
            Session::addMessageAfterRedirect(__('Failed to remove guest habilitation for the user of the device', 'flyvemdm'));
            return false;
         }

         // Check the user still has one or several profiles
         $rows = $profile_User->find("`users_id`='$ownerUserId'", '', '1');
         if (count($rows) == 0) {
            // Delete the user
            $user = new User();
            $user->delete(['id' => $ownerUserId], true);
         }
      }

      // Delete the user account of the agent
      $agentUserId = $this->fields['users_id'];
      $agentUser = new User();
      $agentUser->delete([
            'id'  => $this->fields['users_id'],
      ], true);

      // Delete the MQTT user for the agent
      if (!empty($serial)) {
         $mqttUser = new PluginFlyvemdmMqttuser();
         if ($mqttUser->getFromDBByQuery("WHERE `user` = '$serial'")) {
            if (!$mqttUser->delete(['id' => $mqttUser->getID()], true)) {
               Session::addMessageAfterRedirect(__('Failed to delete MQTT user for the device', 'flyvemdm'));
               return false;
            }
         }
      }

      // Delete the computer associated to the agent
      if (!$computer->delete(['id' => $computerId], true)) {
         Session::addMessageAfterRedirect(__('Failed to delete the device', 'flyvemdm'));
         return false;
      }

      // Delete documents associated to the agent
      $document_Item = new Document_Item();
      $success = $document_Item->deleteByCriteria([
            'itemtype'  => 'PluginFlyvemdmAgent',
            'items_id'  => $this->fields['id']
      ]);
      if (!$success) {
         Session::addMessageAfterRedirect(__('Failed to delete documents attached to the device', 'flyvemdm'));
         return false;
      }

      return true;
   }

   /**
    * Actions done after the UPDATE of the item in the database
    * @param $history store changes history ? (default 1)
    * @return nothing
    */
   public function post_updateItem($history=1) {
      if (in_array('plugin_flyvemdm_fleets_id', $this->updates)) {

         $this->updateSubscription();
         if (isset($this->oldvalues['plugin_flyvemdm_fleets_id'])) {
            $oldFleet = new PluginFlyvemdmFleet();
            $oldFleet->getFromDB($this->oldvalues['plugin_flyvemdm_fleets_id']);
         }
      }

      // If both wipe and lock are enabled for the device, only send wipe command
      if (in_array('wipe', $this->updates) && $this->fields['wipe'] != '0') {
         $this->sendWipeQuery();
      }

      if (in_array('lock', $this->updates) && $this->fields['lock'] != '0') {
         if ($this->fields['wipe'] == '0') {
            $this->sendLockQuery();
         }
      }

      if (in_array('enroll_status', $this->updates) && $this->fields['enroll_status'] == 'unenrolling') {
         $this->sendUnenrollQuery();
      }
   }

   /**
    * Actions done after the restore of the item
    * @return nothing
    */
   public function post_restoreItem() {

      $computer = $this->getComputer();
      if ($computer !== null) {
         $mqttUser = new PluginFlyvemdmMqttuser();
         if ($mqttUser->getFromDB($this->fields['computers_id'])) {
            $mqttUser->update([
                  'id'        => $mqttUser->getID(),
                  'enabled'   => '1'
            ], 0);
         }
      }
   }

   public function post_purgeItem() {
      $this->cleanupSubtopics();
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']             = __s('Agent', "flyvemdm");

      $i = 1;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'name';
      $tab[$i]['name']            = __('Name');
      $tab[$i]['datatype']        = 'itemlink';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('ID');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'number';

      $i++;
      $tab[$i]['table']           = PluginFlyvemdmFleet::getTable();
      $tab[$i]['field']           = 'name';
      $tab[$i]['name']            = __('Fleet', 'flyvemdm');
      $tab[$i]['datatype']        = 'dropdown';

      $i++;
      $tab[$i]['table']           = Computer::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('Computer');
      $tab[$i]['datatype']        = 'dropdown';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = Computer::getTable();
      $tab[$i]['field']           = 'serial';
      $tab[$i]['name']          = __('Serial number');
      $tab[$i]['datatype']        = 'dropdown';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']         = User::getTable();
      $tab[$i]['field']         = 'id';
      $tab[$i]['name']          = __('User');
      $tab[$i]['forcegroupby']  = false;
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['datatype']      = 'dropdown';
      $tab[$i]['joinparams']    = array('beforejoin'
                                          => array('table'      => Computer::getTable(),
                                                   'joinparams' => array('jointype' => '',
                                                         'condition' => '')));

      $i++;
      $tab[$i]['table']           = PluginFlyvemdmFleet::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('Fleet', 'flyvemdm')." - ".__('ID');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'number';

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'last_contact';
      $tab[$i]['name']            = __('last contact', "flyvemdm");
      $tab[$i]['datatype']        = 'datetime';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']         = User::getTable();
      $tab[$i]['field']         = 'realname';
      $tab[$i]['name']          = __('User');
      $tab[$i]['forcegroupby']  = false;
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['datatype']      = 'dropdown';
      $tab[$i]['joinparams']    = array('beforejoin'
            => array('table'      => Computer::getTable(),
                  'joinparams' => array('jointype' => '',
                        'condition' => '')));

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'version';
      $tab[$i]['name']            = __('version', "flyvemdm");
      $tab[$i]['datatype']        = 'string';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'is_online';
      $tab[$i]['name']            = __('online', "flyvemdm");
      $tab[$i]['datatype']        = 'boolean';
      $tab[$i]['massiveaction']   = false;

      return $tab;
   }

   /**
    * Limit search for agents of guest user
    */
   public static function addDefaultJoin() {
      $join = '';

      $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id'));
      $guestProfileId = $config['guest_profiles_id'];
      if ($_SESSION['glpiactiveprofile']['id'] == $guestProfileId) {
         $agentTable = self::getTable();
         $computerTable = Computer::getTable();
         $join = "LEFT JOIN `$computerTable` AS `c` ON `$agentTable`.`computers_id`=`c`.`id` ";
      }

      return $join;
   }

   /**
    * Limit search for agents if guest user
    */
   public static function addDefaultWhere() {
      $where = '';

      $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id'));
      $guestProfileId = $config['guest_profiles_id'];
      if ($_SESSION['glpiactiveprofile']['id'] == $guestProfileId) {
         $agentTable = self::getTable();

         $userId = $_SESSION['glpiID'];
         $where = " AND `c`.`users_id`='$userId'";
      }

      return $where;
   }

   /**
    * Returns the topic the agent shall listen
    *
    * @return string MQTT Topic
    *
    */
   public function getSubscribedTopic() {
      $fleet = new PluginFlyvemdmFleet();
      $subscribedTopic = null;
      if ($fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
         if (! $fleet->fields['is_default']) {
            $subscribedTopic = $fleet->getTopic();
         }
      }
      return $subscribedTopic;
   }

   /**
    * Send to an agent an up to date list of MQTT topics it must subscribe
    */
   public function updateSubscription() {
      $topicToSubscribe = $this->getSubscribedTopic();
      $topicList = array(
            'subscribe' => array(
                  array('topic' => $topicToSubscribe)
            )
      );

      $topic = $this->getTopic();
      if ($topicToSubscribe !== null && $topic !== null) {
         $this->notify("$topic/Command/Subscribe", json_encode($topicList, JSON_UNESCAPED_SLASHES), 0, 1);
      }
   }

   /**
    * get the MQTT topic of the agent
    *
    * @return string|null the permanent MQTT topic of the agent.
    */
   public function getTopic() {
      if ($this->topic === null) {
         $computer = $this->getComputer();
         if ($computer !== null) {
            $serial = $computer->getField('serial');
            if (strlen($serial)) {
               $entity = $this->getField('entities_id');
               $this->topic = "/$entity/agent/$serial";
            }
         }
      }

      return $this->topic;
   }

   /**
    * get an agent from DB by topic
    *
    * @param string|false
    */
   public function getByTopic($topic) {
      global $DB;

      $mqttPath = explode('/', $topic);
      if (isset($mqttPath[3])) {
         if ($mqttPath[2] == 'agent') {
            $entity = intval($mqttPath[1]);
            $serial = $DB->escape($mqttPath[3]);
            if (strlen($serial)) {
               $computerTable = Computer::getTable();
               $agentTable = self::getTable();
               return $this->getFromDBByQuery("LEFT JOIN `$computerTable` `c` ON (
                     `c`.`id` = `$agentTable`.`computers_id`
                     )
                     WHERE `$agentTable`.`entities_id`='$entity' AND `c`.`serial` = '$serial'"
               );
            }
         }
      }

      return false;
   }

   /**
    * unsibscribe from a fleet
    */
   public function unsubscribe() {
      $this->update([
            'id' => $this->getID(),
            'plugin_flyvemdm_fleets_id' => null
      ]);
      $topic = $this->getTopic();
      if ($topic !== null) {
         $topic = $topic . "/Subscription";
         $this->notify($topic, json_encode(array(), JSON_UNESCAPED_SLASHES));
      }
   }

   /**
    * Checks if the data provided for enrollment satisfy our requirements
    * @param Array $authFactors
    * @return integer enroll method
    */
   protected static function checkChallengeCombinations($authFactors) {
      $method = self::ENROLL_DENY;

      if (array_key_exists('email', $authFactors) && array_key_exists('agentToken', $authFactors)) {
         // require challenge on email and an agent token only
         if (count($authFactors) == 2) {
            $method = self::ENROLL_AGENT_TOKEN;
         }

      } else if (array_key_exists('entityToken', $authFactors)) {
         // or require challenge on a entity token only
         if (count($authFactors) == 1) {
            $method = self::ENROLL_ENTITY_TOKEN;
         }
      }

      return $method;
   }

   /**
    * Attempt to enroll using an invitation token
    * @param array $input Enrollment data
    */
   protected function enrollByInvitationToken($input) {
      $invitationToken  = isset($input['_invitation_token']) ? $input['_invitation_token'] : null;
      $email            = isset($input['_email']) ? $input['_email'] : null;
      $serial           = isset($input['_serial']) ? $input['_serial'] : null;
      $uuid             = isset($input['_uuid']) ? $input['_uuid'] : null;
      $csr              = isset($input['csr']) ? $input['csr'] : null;
      $firstname        = isset($input['firstname']) ? $input['firstname'] : null;
      $lastname         = isset($input['lastname']) ? $input['lastname'] : null;
      $version          = isset($input['version']) ? $input['version'] : null;

      $input = array();

      $config = Config::getConfigurationValues("flyvemdm", [
            'mqtt_broker_tls',
            'mqtt_use_client_cert',
            'debug_noexpire',
            'computertypes_id',
            'agentusercategories_id',
            'agent_profiles_id',
      ]);

      // Find the invitation
      $invitation = new PluginFlyvemdmInvitation();
      if (!$invitation->getFromDBByToken($invitationToken)) {
         $this->filterMessages(__('Invitation token invalid', 'flyvemdm'));
         return false;
      }

      if (empty($serial) && empty($uuid)) {
         $event = __('One of serial and uuid is mandatory', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      if (empty($version)) {
         $event = __('Agent version missing', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      if (preg_match(PluginFlyvemdmCommon::SEMVER_VERSION_REGEX, $version) !== 1) {
         $event = __('Bad agent version', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      // Check the invitation is pending
      if ($invitation->getField('status') != 'pending') {
         $event = __('Invitation is not pending', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      // Check the token has not yet expired
      if ($invitation->getField('expiration_date') === null) {
         $event = __('Expiration date of the invitation is not set', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }
      $currentDatetime = new DateTime("now", new DateTimeZone("UTC"));
      $expirationDatetime = new DateTime($invitation->getField('expiration_date'), new DateTimeZone("UTC"));
      if ($currentDatetime >= $expirationDatetime) {
         $event = __('Invitation token expired', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      // Check the given email belongs to the same user than the user in the invitation
      $user = new User();
      $condition = "`glpi_users`.`id`='" . $invitation->getField('users_id') . "'";
      if ($user->getFromDBbyEmail($email, $condition) === false) {
         $event = __('wrong email address', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }
      $userId = $user->getId();

      // Check the serial does not exists in the entity
      $computer = new Computer();
      $entityId = $invitation->getField('entities_id');
      $rows = $computer->find("`entities_id`='$entityId' AND `serial`='$serial'");
      if (count($rows) > 0) {
         $event = __('The serial already exists', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      //create agent user account
      $agentAccount = new User();
      $agentAccount->add([
         'usercategories_id' => $config['agentusercategories_id'],
         'name'              => $serial,
         '_profiles_id'      => $config['agent_profiles_id'],
         'profiles_id'       => $config['agent_profiles_id'],      // Default profile when user logs in
         '_entities_id'      => $entityId,
         '_is_recursive'     => 0,
      ]);

      if ($agentAccount->isNewItem()) {
         $event = __('Cannot create a user account for the agent', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }
      $agentToken = User::getToken($agentAccount->getID(), 'api_token');
      if ($agentToken === false) {
         $event = __('Cannot create the API token for the agent', 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      //sign the agent's certificate (if TLS enabled)
      if ($config['mqtt_broker_tls'] != '0' && $config['mqtt_use_client_cert'] != '0') {
         $answer = self::signCertificate($csr);
         $crt = isset($answer['crt']) ? $answer['crt'] : false;
         if ($crt === false) {
            $event = __("Failed to sign the certificate", 'flyvemdm')  . "\n " . $answer['message'];
            $this->filterMessages($event);
            $this->logInvitationEvent($invitation, $event);
            return false;
         }
         $input['certificate'] = $crt;
      } else {
         $input['certificate'] = '';
      }

      // Prepare invitation update
      $invitationInput = [
            'id'                 => $invitation->getID(),
            'status'             => 'done'
      ];

      // Invalidate the token
      if ($config['debug_noexpire'] == '0') {
         $invitationInput['expiration_date'] = '0000-00-00 00:00:00';
         $invitationInput['status']          = 'done';

         // Update the invitation
         if (!$invitation->update($invitationInput)) {
            $event = __("Failed to update the invitation", 'flyvemdm');
            $this->filterMessages($event);
            $this->logInvitationEvent($invitation, $event);
            return false;
         }
      }

      // Update the computer's serial and type
      // Create a new computer for the device being enrolled
      // TODO : Enable localization of the type
      $computerTypeId = $config['computertypes_id'];
      if ($computerTypeId == -1 || $computerTypeId === false) {
         $computerTypeId = 0;
      }

      // Create the device
      $computer = new Computer();
      $computerId = $computer->add(array(
            'name'         => $email,
            'users_id'     => $userId,
            'entities_id'  => $entityId,
            'serial'       => $serial,
            'uuid'         => $uuid,
      ));
      if ($computerId === false) {
         $event = __("Cannot create the device", 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      // Lock the name field of the device
      $fiLock = new PluginFusioninventoryLock();
      $fiLock->addLocks('Computer', $computerId, array('name', 'users_id'));

      // Create the agent
      $defaultFleet = PluginFlyvemdmFleet::getDefaultFleet();
      if ($defaultFleet === null) {
         $computer->delete(['id' => $computerId]);
         $event = __("No default fleet available for the device", 'flyvemdm');
         $this->filterMessages($event);
         $this->logInvitationEvent($invitation, $event);
         return false;
      }

      // Enrollment is about to succeed, then update the user
      if (!empty($user->getField('firstname')) || !empty($user->getField('lastname'))) {
         $user->update([
               'id'        => $userId,
               'firstname' => $firstname,
               'realname'  => $lastname,
         ]);
      }

      // Enrollment is about to succeed then cleanup subtopics
      $this->fields['computers_id'] = $computerId;
      $this->fields['entities_id']  = $entityId;
      $this->cleanupSubtopics();

      $input['name']                      = $email;
      $input['computers_id']              = $computerId;
      $input['entities_id']               = $entityId;
      $input['plugin_flyvemdm_fleets_id'] = $defaultFleet->getID();
      $input['_invitations_id']           = $invitation->getID();
      $input['enroll_status']             = 'enrolled';
      $input['version']                   = $version;
      $input['users_id']                  = $agentAccount->getID();
      return $input;

   }

   /**
    * @param string $serial
    * @param array $authFactors
    * @param string $csr Certificate Signing Request from the agent
    * @param &string $notFoundMessage Contains the error message if the enrollment failed
    * @return boolean|PluginFlyvemdmAgent
    *
    */
   //protected static function enrollByEntityToken($serial, $authFactors, $csr, &$errorMessage) {
      //global $DB;

      //$token = $DB->escape($authFactors['entityToken']);

      //// Find an entity matching the given token
      //$entity = new PluginFlyvemdmEntityconfig();
      //if (! $entity->getFromDBByQuery("WHERE `enroll_token`='$token'")) {
      //   $errorMessage = "no entity token not found";
      //   return false;
      //}

      //// Create a new computer for the device being enrolled
      //// TODO : Enable localization of the type
      //$computerType = new ComputerType();
      //$computerTypeId = $computerType->import(array('name' => 'Smartphone'));
      //if ($computerTypeId == -1 || $computerTypeId === false) {
      //   $computerTypeId = 0;
      //}
      //$computer = new Computer();
      //$condition = "`serial`='" . $DB->escape($serial) . "' AND `entities_id`='" . $entity->getID() . "'";
      //$computerCollection = $computer->find($condition);
      //if (count($computerCollection) > 1) {
      //   $errorMessage = "failed to find the computer";
      //   return false;
      //}
      //if (count($computerCollection) == 1) {

      //   reset($computerCollection);
      //   $computer->getFromDB(key($computerCollection));
      //   $computerId = $computer->getID();

      //} else {
      //   $computerId = $computer->add(array(
      //      'entities_id'        => $entity->getID(),
      //      'serial'             => $serial,
      //      'computertypes_id'   => $computerTypeId
      //   ));

      //   if ($computerId === false) {
      //      $errorMessage = "failed to create the computer";
      //      return false;
      //   }
      //}

      //if (! $computerId > 0) {
      //   $errorMessage = "failed to update the computer";
      //   return false;
      //}

      //// Create an agent for this device, linked to the new computer
      //$agent = new PluginFlyvemdmAgent();
      //$condition = "`computers_id`='$computerId'";
      //$agentCollection = $agent->find($condition);
      //if (count($agentCollection) > 1) {
      //   return false;
      //}
      //if (count($agentCollection) == 1) {

      //   reset($agentCollection);
      //   $agent->getFromDB(key($agentCollection));
      //   $agentId = $agent->getId();

      //} else {
      //   $agentId = $agent->add(array(
      //         'entities_id'     => $entity->getID(),
      //         'computers_id'    => $computer->getID(),
      //         'token_expire'    => '0000-00-00 00:00:00'
      //   ));
      //}

      //if (! $agentId > 0) {
      //   return false;
      //}

      //return $agent;

   //}

   /**
    * Erase delete persisted MQTT topics of the agent
    */
   public function cleanupSubtopics() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         foreach (self::getTopicsToCleanup() as $subTopic) {
            $this->notify("$topic/$subTopic", '', 0, 1);
         }
      }
   }

   /**
    * list of topics to cleanup on unenrollment or on enrollment
    *
    * @return string[]
    */
   public static function getTopicsToCleanup() {
      return array(
         "Command/Subscribe",
         "Command/Ping",
         "Command/Geolocate",
         "Command/Inventory",
         "Command/Lock",
         "Command/Wipe",
         "Command/Unenroll",
         "Configuration",
         "application",
         "file",
         "policies",
         "encryption",
         "camera",
         "connectivity",
      );
   }

   /**
    * Send an geolocation request to the agent
    * @return boolean
    */
   protected function sendGeolocationQuery(&$errorMessage) {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $computerId = $this->fields['computers_id'];
         $geolocation = new PluginFlyvemdmGeolocation();
         $lastPositionRows = $geolocation->find("`computers_id`='$computerId'", '`date` DESC, `id` DESC', '1');
         $lastPosition = array_pop($lastPositionRows);

         $this->notify("$topic/Command/Geolocate", '{"query":"Geolocate"}', 0, 0);

         return $this->pollGeolocationAnswer($lastPosition, $errorMessage);
      }

      $errorMessage = __('Timeout requesting position', 'flyvemdm');
      return false;
   }

   /**
    * Polls in the DB for a new geolocation entry with ID higher than the given one
    * Timeouts if no new entry after a few seconds
    * @param unknown $lastPosition
    * @param string $errorMessage the error message to return to the caller
    * @return boolean true if a new position found before timeout
    */
   protected function pollGeolocationAnswer($lastPosition, &$errorMessage) {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $geolocation = new PluginFlyvemdmGeolocation();
         $computerId = $this->fields['computers_id'];

         // Wait for a reply within a short delay
         $loopCount = 25;
         while ($loopCount > 0) {
            usleep(200000); // 200 milliseconds
            $loopCount--;
            $updatedPositionRows = $geolocation->find("`computers_id`='$computerId'", '`date` DESC, `id` DESC', '1');
            $updatedPosition = array_pop($updatedPositionRows);
            if ($lastPosition === null && $updatedPosition !== null
                  || $lastPosition !== null && $lastPosition['id'] != $updatedPosition['id']) {
               if ($updatedPosition['latitude'] == 'na') {
                  $errorMessage = __('GPS is turned off or is not ready', 'flyvemdm');
                  return false;
               } else {
                  return true;
               }
            }
         }
      }
   }

   /**
    * Send an inventory request to the device
    * @return boolean
    */
   protected function sendInventoryQuery() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $computerId = $this->fields['computers_id'];

         $inventory = new PluginFusioninventoryInventoryComputerComputer();
         $inventoryRows = $inventory->find("`computers_id`='$computerId'", '', '1');
         $lastInventory = array_pop($inventoryRows);

         $this->notify("$topic/Command/Inventory", '{"query":"Inventory"}', 0, 0);

         // Wait for a reply within a short delay
         $loopCount = 5 * 15; // 15 seconds
         while ($loopCount > 0) {
            usleep(200000); // 200 milliseconds
            $loopCount--;
            $inventoryRows = $inventory->find("`computers_id`='$computerId'", '', '1');
            $updatedInventory = array_pop($inventoryRows);
            if ($lastInventory === null && $updatedInventory !== null
                  || $lastInventory !== null && $lastInventory != $updatedInventory) {
               return true;
            }
         }
      }

      return false;
   }

   /**
    * Sends a message on the subtopic dedicated to ping requests
    * @return boolean
    */
   protected function sendPingQuery() {
      $topic = $this->getTopic();
      if ($topic !== null) {
         $message = [
               'query'  => 'Ping'
         ];
         $this->notify("$topic/Command/Ping", json_encode($message, JSON_UNESCAPED_SLASHES), 0, 0);

         // Wait for a reply within a short delay
         $loopCount = 25;
         $updatedAgent = new self();
         while ($loopCount > 0) {
            usleep(200000); // 200 milliseconds
            $loopCount--;
            $updatedAgent->getFromDB($this->getID());
            if ($updatedAgent->getField('last_contact') != $this->fields['last_contact']) {
               return true;
            }
         }
      }

      return false;
   }

   /**
    * Attempts to sign the certificate against the CA
    * @param String $csr Certificate signing request
    */
   protected static function signCertificate($csr) {
      $config = Config::getConfigurationValues('flyvemdm', array('ssl_cert_url'));
      if ($config === null) {
         return false;
      }
      $url = $config['ssl_cert_url'];

      $csr = urlencode($csr);

      $json = file_get_contents("$url/csr_sign.php?csr=$csr");
      if ($json === false) {
         return false;
      }

      $answer = json_decode($json, true);

      return $answer;
   }

   /**
    * @see PluginFlyvemdmNotifiable::getAgents()
    */
   public function getAgents() {
      return array($this);
   }

   /**
    * @see PluginFlyvemdmNotifiable::getPackages()
    */
   public function getPackages() {
      if ($this->getID() > 0) {

         $fleet = new PluginFlyvemdmFleet();
         if ($fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
            return $fleet->getPackages();
         }
      }

      return array();
   }

   /**
    * @see PluginFlyvemdmNotifiable::getFiles()
    */
   public function getFiles() {
      if ($this->getID() > 0) {
         $fleet = new PluginFlyvemdmFleet();
         if ($fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
            return $fleet->getFiles();
         }
      }

      return array();
   }

   /**
    * @see PluginFlyvemdmNotifiable::getFleet()
    */
   public function getFleet() {
      $fleet = null;

      if (! $this->isNewItem()) {
         // The agent exists in DB
         $fleet = new PluginFlyvemdmFleet();
         if ($fleet->isNewID($this->fields['plugin_flyvemdm_fleets_id'])) {
            $fleet = null;
         } else {
            if (!$fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
               $fleet = null;
            }
         }
      }

      return $fleet;
   }

   /**
    * Get the user owner of the agent's device
    */
   public function getOwner() {
      $computer = new Computer();
      if ($computer->getFromDB($this->fields['computers_id'])) {
         $user = new User();
         if ($user->getFromDB($computer->getField('users_id'))) {
            return $user;
         }
      }

      return null;
   }

   /**
    * Determine the enrollment method depending on input data
    * @param array $input
    */
   protected function chooseEnrollMethod($input) {
      // Serial is mandatory
      if (!isset($input['_serial'])) {
         return self::ENROLL_DENY;
      }

      if (isset($input['_email'])
            && isset($input['_invitation_token'])) {
         return self::ENROLL_INVITATION_TOKEN;
      } else if (isset($input['_entity_token'])) {
         return self::ENROLL_ENTITY_TOKEN;
      }

      return self::ENROLL_DENY;;
   }

   /**
    * Creates virtual fields with enrollment data
    */
   protected function setupMqttAccess() {
      if (!isset($_SESSION['glpiID'])) {
         return;
      }

      if ($user = $this->getOwner()) {
         $config = Config::getConfigurationValues('flyvemdm', array(
               'guest_profiles_id',
               'android_bugcollecctor_url',
               'android_bugcollector_login',
               'android_bugcollector_passwd',
               'mqtt_broker_address',
               'mqtt_broker_port',
               'mqtt_broker_tls',
         ));
         $guestProfileId = $config['guest_profiles_id'];
         if ($user->getID() == $_SESSION['glpiID'] && $_SESSION['glpiactiveprofile']['id'] == $guestProfileId) {
            $mqttClearPassword = '';

            // Create, or re-eanble the mqtt user for the device
            $computer = new Computer();
            if (!$computer->getFromDB($this->fields['computers_id'])) {
               // TODO : failed to find the computer
               return;
            } else {
               $serial = $computer->getField('serial');
               if (!empty($serial)) {
                  $acls = [
                        [
                              'topic'        => $this->getTopic() . '/Status/#',
                              'access_level' => PluginFlyvemdmMqttacl::MQTTACL_WRITE
                        ],
                        [
                              'topic'        => $this->getTopic() . '/Command/#',
                              'access_level' => PluginFlyvemdmMqttacl::MQTTACL_READ
                        ],
                        [
                              'topic'        => $this->getTopic() . '/FlyvemdmManifest/#',
                              'access_level' => PluginFlyvemdmMqttacl::MQTTACL_WRITE
                        ],
                        [
                              'topic'        => '/FlyvemdmManifest/#',
                              'access_level' => PluginFlyvemdmMqttacl::MQTTACL_READ
                        ],
                  ];

                  $mqttUser = new PluginFlyvemdmMqttuser();
                  $mqttClearPassword = PluginFlyvemdmMqttuser::getRandomPassword();
                  Toolbox::LogInFile('mqttpass', "$serial $mqttClearPassword\n");
                  if (!$mqttUser->getByUser($serial)) {
                     // The user does not exists
                     $mqttUser->add([
                           'user'         => $serial,
                           'enabled'      => '1',
                           'password'     => $mqttClearPassword,
                           '_acl'         => $acls,
                           '_reset_acls'  => true,
                     ]);
                  } else {
                     // The user exists
                     $mqttUser->update([
                           'id'        => $mqttUser->getID(),
                           'enabled'   => '1',
                           'password'  => $mqttClearPassword,
                           '_acl'      => $acls,
                           '_reset_acls'  => true,
                      ]);
                  }
               }
            }

            // The request comes from the owner of the device or the device itself, mandated by the user
            $this->fields['topic']                       = $this->getTopic();
            $this->fields['mqttpasswd']                  = $mqttClearPassword;
            $this->fields['broker']                      = $config['mqtt_broker_address'];
            $this->fields['port']                        = $config['mqtt_broker_port'];
            $this->fields['tls']                         = $config['mqtt_broker_tls'];
            $this->fields['android_bugcollecctor_url']   = $config['android_bugcollecctor_url'];
            $this->fields['android_bugcollector_login']  = $config['android_bugcollector_login'];
            $this->fields['android_bugcollector_passwd'] = $config['android_bugcollector_passwd'];
         }
      }
   }

   /**
    * If debug node is disabled, disable detailed error messages
    * @param unknown $error
    */
   protected function filterMessages($error) {
      $config = Config::getConfigurationValues('flyvemdm', array('debug_enrolment'));
      if ($config['debug_enrolment'] == 0) {
         Session::addMessageAfterRedirect(__('Enrollment failed', 'flyvemdm'));
      } else {
         Session::addMessageAfterRedirect($error);
      }
   }
   protected function logInvitationEvent(PluginFlyvemdmInvitation $invitation, $event) {
      $invitationLog = new PluginFlyvemdmInvitationlog();
      $invitationLog->add([
            'plugin_flyvemdm_invitations_id' => $invitation->getID(),
            'event'                          => $event
      ]);
   }

   /**
    * Update settings related to fleet change
    * @param PluginFlyvemdmFleet $old old fleet
    * @param PluginFlyvemdmFleet $new new fleet
    */
   protected function changeFleet(PluginFlyvemdmFleet $old, PluginFlyvemdmFleet $new) {
      // Update MQTT account
      $computerId = $this->getField('computers_id');
      $mqttUser = new PluginFlyvemdmMqttuser();
      if ($mqttUser->getFromDBByQuery("LEFT JOIN `glpi_computers` `c` ON (`c`.`serial`=`user`) WHERE `c`.`id`='$computerId'")) {
         $mqttAcl = new PluginFlyvemdmMqttacl();
         if ($old->getField('is_default') == '0') {
            $mqttAcl->getFromDBByQuery("WHERE `topic`='" . $old->getTopic() . "/#'
                  AND `plugin_flyvemdm_mqttusers_id`='" . $mqttUser->getID() . "'");
            if ($new->getField('is_default') != '0') {
               $mqttAcl->delete(['id' => $mqttAcl->getID()]);

            } else {
               $mqttAcl->update([
                     'id'                             => $mqttAcl->getID(),
                     'topic'                          => $new->getTopic() . '/#',
                     'access_level'                   => PluginFlyvemdmMqttacl::MQTTACL_READ
               ]);
            }
         } else {
            $mqttAcl->add([
                  'plugin_flyvemdm_mqttusers_id'   => $mqttUser->getID(),
                  'topic'                          => $new->getTopic() . '/#',
                  'access_level'                   => PluginFlyvemdmMqttacl::MQTTACL_READ
            ]);
         }
      }
   }

   /**
    * Get the computer associatated to the agent
    * @return NULL|Computer
    */
   public function getComputer() {
      if (!isset($this->fields['computers_id'])) {
         return null;
      }

      $computer = new Computer();
      if (!$computer->getFromDB($this->fields['computers_id'])) {
         return null;
      }

      return $computer;
   }

   /**
    *
    * @see PluginFlyvemdmNotifiable::notify()
    */
   public function notify($topic, $mqttMessage, $qos = 0, $retain = 0) {
      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $mqttClient->publish($topic, $mqttMessage, $qos, $retain);
   }

   /**
    * purge agents in the entity being purged
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      $agent = new static();
      $agent->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);
   }
}
