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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmPackage extends CommonDBTM {

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname                   = 'flyvemdm:package';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = true;

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('Package', 'Packages', $nb, "flyvemdm");
   }

   public static function getMenuPicture() {
      return '../pics/picto-package.png';
   }

   /**
    * @see CommonGLPI::defineTabs()
    */
   public function defineTabs($options = array()) {
      $tab = array();
      $this->addDefaultFormTab($tab);
      $this->addStandardTab('Notepad', $tab, $options);
      $this->addStandardTab('Log', $tab, $options);

      return $tab;
   }

   /**
    * Returns the tab name of this itemtype, depending on the itemtype on which it will be displayed
    * If the tab shall not display then returns an empty string
    * @param CommonGLPI $item on which the tab will show
    * @param number $withtemplate template mode for $item : 0 = no template - 1 = edit template - 2 = from template
    *
    * @return string
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {
         case 'Software' :
            return _n('Package Flyve MDM', 'Packages Flyve MDM', Session::getPluralNumber(), "flyvemdm");

      }
      return '';
   }

   /**
    *  Display the content of the tab provided by this itemtype
    * @param CommonGLPI $item
    * @param number $tabnum
    * @param number $withtemplate
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'Software') {
         self::showForSoftware($item);
         return true;
      }
   }

   /**
    * Display a form to view, create or edit
    * @param integer $ID ID of the item to show
    * @param array $options
    */
   public function showForm($ID, $options=array()) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $twig = plugin_flyvemdm_getTemplateEngine();
      $fields              = $this->fields;
      $objectName          = autoName($this->fields["name"], "name",
            (isset($options['withtemplate']) && $options['withtemplate'] == 2),
            $this->getType(), -1);
      $fields['filesize']  = PluginFlyvemdmCommon::convertToGiB($fields['filesize']);
      $data = [
            'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
            'canUpdate'    => (!$this->isNewID($ID)) && ($this->canUpdate() > 0) || $this->isNewID($ID),
            'isNewID'      => $this->isNewID($ID),
            'package'      => $fields,
            'upload'       => Html::file(['name' => 'file', 'display' => false]),
      ];
      echo $twig->render('package.html', $data);

      $this->showFormButtons($options);
   }

   /**
    * Gets the maximum file size allowed for uploads from PHP configuration
    * @return integer maximum file size
    */
   protected static function getMaxFileSize() {
      $val = trim(ini_get('post_max_size'));
      $last = strtolower($val[strlen($val)-1]);
      switch ($last) {
         case 'g':
            $val *= 1024;
         case 'm':
            $val *= 1024;
         case 'k':
            $val *= 1024;
      }

      return $val;
   }

   /**
    *
    * @see CommonDBTM::addNeededInfoToInput()
    */
   public function addNeededInfoToInput($input) {
      $input['entities_id'] = $_SESSION['glpiactive_entity'];

      return $input;
   }

   /**
    *
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      // Find the added file
      if (isset($_POST['_file'][0]) && is_string($_POST['_file'][0])) {
         // from GLPI UI
         $actualFilename = $_POST['_file'][0];
         $uploadedFile = GLPI_TMP_DIR . '/' . $_POST['_file'][0];
      } else {
         // from API
         if (!isset($_FILES['file'])) {
            Session::addMessageAfterRedirect(__('No file uploaded', "flyvemdm"));
            return false;
         }

         if (!$_FILES['file']['error'] == 0) {
            if (!$_FILES['file']['error'] == 4) {
               Session::addMessageAfterRedirect(__('File upload failed', 'flyvemdm'));
            }
            return false;
         }

         $destination = GLPI_TMP_DIR . '/' . $_FILES['file']['name'];
         if (is_readable($_FILES['file']['tmp_name']) && !is_readable($destination)) {
            // With GLPI < 9.2, the file was not moved by the API

            // Move the file to GLPI_TMP_DIR
            if (!is_dir(GLPI_TMP_DIR)) {
               Session::addMessageAfterRedirect(__('Temp directory doesn\'t exist', 'flyvemdm'), false, ERROR);
               return false;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
               Session::addMessageAfterRedirect(__('Failed to save the file', 'flyvemdm'));
               return false;
            }
         }

         $actualFilename = $_FILES['file']['name'];
         $uploadedFile = $destination;
      }

      if (!isset($actualFilename)) {
         Session::addMessageAfterRedirect(__('File uploaded without name', "flyvemdm"));
         return false;
      }

      // Check the extension of the uploaded file
      $fileExtension = pathinfo($actualFilename, PATHINFO_EXTENSION);
      if (!in_array($fileExtension, array('apk', 'upk'))) {
         Session::addMessageAfterRedirect(__('Only APK and UPK files are allowed', 'flyvemdm'));
         return false;
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      try {
         $input['filename'] = $this->fields['entities_id'] . '/' . uniqid() . '_' . basename($uploadedFile);
         $destination = FLYVEMDM_PACKAGE_PATH . '/' . $input['filename'];
         $this->createEntityDirectory(dirname($destination));
         if (rename($uploadedFile, $destination)) {
            $fileExtension = pathinfo($destination, PATHINFO_EXTENSION);
            $filename = pathinfo($destination, PATHINFO_FILENAME);
            if ($fileExtension == 'apk') {
               $apk = new \ApkParser\Parser($destination);
            } else if ($fileExtension == 'upk') {
               $upkParser = new PluginFlyvemdmUpkparser($destination);
               $apk = $upkParser->getApkParser();
               if (!($apk instanceof \ApkParser\Parser)) {
                  Session::addMessageAfterRedirect(__('Could not parse the UPK file', 'flyvemdm'));
                  return false;
               }
            }
            $manifest               = $apk->getManifest();
            $iconResourceId         = $manifest->getApplication()->getIcon();
            $labelResourceId        = $manifest->getApplication()->getLabel();
            $iconResources          = $apk->getResources($iconResourceId);
            $apkLabel               = $apk->getResources($labelResourceId);
            $input['icon']          = base64_encode(stream_get_contents($apk->getStream($iconResources[0])));
            $input['name']          = $manifest->getPackageName();
            if ((!isset($input['alias'])) || (strlen($input['alias']) == 0)) {
               $input['alias']         = $apkLabel[0]; // Get the first item
            }
            $input['version']       = $manifest->getVersionName();
            $input['version_code']  = $manifest->getVersionCode();
            $input['filesize']      = fileSize($destination);
            $input['dl_filename']   = basename($uploadedFile);
         } else {
            if (!is_writable(dirname($destination))) {
               $destination = dirname($destination);
               Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Directory '$destination' is not writeable");
            }
            Session::addMessageAfterRedirect(__('Unable to save the file', 'flyvemdm'));
            $input = false;
         }
      } catch (Exception $e) {
         // Ignore exceptions for now
         Session::addMessageAfterRedirect(__('Could not parse the APK file', 'flyvemdm'));
         $input = false;
      }

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      // Find the added file
      if (isset($_POST['_file'][0]) && is_string($_POST['_file'][0])) {
         // from GLPI UI
         $actualFilename = $_POST['_file'][0];
         $uploadedFile = GLPI_TMP_DIR."/".$_POST['_file'][0];
         if (!isset($_FILES['file']['name'])) {
            Session::addMessageAfterRedirect(__('File uploaded without name', "flyvemdm"));
            return false;
         }
      } else {
         // from API
         if (isset($_FILES['file']['error'])) {
            if (!$_FILES['file']['error'] == 0) {
               if (!$_FILES['file']['error'] == 4) {
                  Session::addMessageAfterRedirect(__('File upload failed', "flyvemdm"));
               }
               return false;
            }

            $destination = GLPI_TMP_DIR . '/' . $_FILES['file']['name'];
            if (is_readable($_FILES['file']['tmp_name']) && !is_readable($destination)) {
               // Move the file to GLPI_TMP_DIR
               if (!is_dir(GLPI_TMP_DIR)) {
                  Session::addMessageAfterRedirect(__("Temp directory doesn't exist"), false, ERROR);
                  return false;
               }

               // With GLPI < 9.2, the file was not moved by the API
               if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                  return false;
               }
            }

            $actualFilename = $_FILES['file']['name'];
            $uploadedFile = $destination;
         }
      }

      try {
         $input['filename'] = $this->fields['entities_id'] . "/" . uniqid() . "_" . basename($uploadedFile);
         $destination = FLYVEMDM_PACKAGE_PATH . "/" . $input['filename'];
         $this->createEntityDirectory(dirname($destination));
         if (rename($uploadedFile, $destination)) {
            if ($fileExtension == "apk") {
               $apk = new \ApkParser\Parser($destination);
            } else if ($fileExtension == "upk") {
               $upkParser = new PluginFlyvemdmUpkparser($destination);
               $apk = $upkParser->getApkParser();
               if (!($apk instanceof \ApkParser\Parser)) {
                  Session::addMessageAfterRedirect(__('Could not parse the UPK file', "flyvemdm"));
                  return false;
               }
            }
            $manifest               = $apk->getManifest();
            $iconResourceId         = $manifest->getApplication()->getIcon();
            $labelResourceId        = $manifest->getApplication()->getLabel();
            $iconResources          = $apk->getResources($iconResourceId);
            $apkLabel               = $apk->getResources($labelResourceId);
            $input['icon']          = base64_encode(stream_get_contents($apk->getStream($iconResources[0])));
            $input['name']          = $manifest->getPackageName();
            $input['version']       = $manifest->getVersionName();
            $input['version_code']  = $manifest->getVersionCode();
            $input['filesize']      = fileSize($destination);
            $input['dl_filename']   = basename($uploadedFile);
            if ($filename != $this->fields['filename']) {
               unlink(FLYVEMDM_PACKAGE_PATH . "/" . $this->fields['filename']);
            }
         } else {
            if (!is_writable(dirname($destination))) {
               $destination = dirname($destination);
               Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Directory '$destination' is not writeable");
            }
            Session::addMessageAfterRedirect(__('Unable to save the file', "flyvemdm"));
            $input = false;
         }
      } catch (Exception $e) {
         // Ignore exceptions for now
         $input = false;
      }

      return $input;
   }

   public function post_getFromDB() {
      // Check the user can view this itemtype and can view this item
      if ($this->canView() && $this->canViewItem()) {
         if (isAPI()
             && (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/octet-stream'
                 || isset($_GET['alt']) && $_GET['alt'] == 'media')) {
            $this->sendFile(); // and terminate script
         }
      }
   }

   /**
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      if (isset($this->oldvalues['filename'])) {
         $itemtype = $this->getType();
         $itemId = $this->getID();

         $task = new PluginFlyvemdmTask();
         $taskCol = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
         $fleet = new PluginFlyvemdmFleet();
         $policyFactory = new PluginFlyvemdmPolicyFactory();
         foreach ($taskCol as $taskId => $taskRow) {
            $fleetId = $taskRow['plugin_flyvemdm_fleets_id'];
            if ($fleet->getFromDB($fleetId)) {
               Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Could not find fleet id = '$fleetId'");
               continue;
            }
            $policy = $policyFactory->createFromDBByID($taskRow['plugin_flyvemdm_policies_id']);
            if ($task->getFromDB($taskId)) {
               $task->updateQueue($fleet, $policy->getGroup());
            }
         }
      }
   }

   /**
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      $task = new PluginFlyvemdmTask();
      return $task->deleteByCriteria(array(
            'itemtype'  => $this->getType(),
            'items_id'  => $this->getID()
      ));
   }

   /**
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      $filename = FLYVEMDM_PACKAGE_PATH . "/" . $this->fields['filename'];
      if (is_writable($filename)) {
         unlink($filename);
      }
   }

   /**
    * Create a directory
    * @param string $dir
    */
   protected function createEntityDirectory($dir) {
      if (!is_dir($dir)) {
         @mkdir($dir, 0770, true);
      }
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      $tab = array();
      $tab['common']                 = __s('Package', "flyvemdm");

      $i = 1;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'name';
      $tab[$i]['name']                = __('Name');
      $tab[$i]['datatype']            = 'itemlink';
      $tab[$i]['massiveaction']       = false;

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'alias';
      $tab[$i]['name']                = __('alias', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'version';
      $tab[$i]['name']                = __('version', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'icon';
      $tab[$i]['name']                = __('icon', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'image';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'filesize';
      $tab[$i]['name']                = __('filesize', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      return $tab;
   }

   /**
    * Get the download URL for the application
    * @return boolean|string
    */
   public function getFileURL() {
      $config = Config::getConfigurationValues('flyvemdm', array('deploy_base_url'));
      $deployBaseURL = $config['deploy_base_url'];

      if ($deployBaseURL === null) {
         return false;
      }

      $URL = $deployBaseURL . '/package/' . $this->fields['filename'];
      return $URL;
   }

   /**
    * Sends a file
    */
   protected function sendFile() {
      $streamSource = FLYVEMDM_PACKAGE_PATH . "/" . $this->fields['filename'];

      if (!file_exists($streamSource) || !is_file($streamSource)) {
         header("HTTP/1.0 404 Not Found");
         exit(0);
      }

      $size = filesize($streamSource);
      $begin = 0;
      $end = $size - 1;
      $mimeType = 'application/octet-stream';
      $time = date('r', filemtime($streamSource));

      $fileHandle = @fopen($streamSource, 'rb');
      if (!$fileHandle) {
         header ("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

      if (isset($_SERVER['HTTP_RANGE'])) {
         $matches = null;
         if (preg_match('/bytes=\h*(\d+)?-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
            if (!empty($matches[1])) {
               $begin = intval($matches[1]);
            }
            if (!empty($matches[2])) {
               $end = min(intval($matches[2]), $end);
            }
         }
      }

      // seek to the begining of the range
      $currentPosition = $begin;
      if (fseek($fileHandle, $begin, SEEK_SET) < 0) {
         header ("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

      // send headers to ensure the client is able to detect an corrupted download
      // example : less bytes than the expected range
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: attachment; filename=\"" . $this->fields['dl_filename'] . "\"");
      header("Content-type: $mimeType");
      header("Last-Modified: $time");
      header('Accept-Ranges: bytes');
      header('Content-Length:' . ($end - $begin + 1));
      header("Content-Range: bytes $begin-$end/$size");
      header("Content-Transfer-Encoding: binary\n");
      header('Connection: close');

      if ($begin > 0 || $end < $size - 1) {
         header('HTTP/1.0 206 Partial Content');
      } else {
         header('HTTP/1.0 200 OK');
      }

      // Sends bytes until the end of the range or connection closed
      while (!feof($fileHandle) && $currentPosition < $end && (connection_status() == 0)) {
         // allow a few seconds to send a few KB.
         set_time_limit(10);
         $content = fread($fileHandle, min(1024 * 16, $end - $currentPosition + 1));
         if ($content === false) {
            header("HTTP/1.0 500 Internal Server Error", true); // Replace previously sent headers
            exit(0);
         } else {
            print $content;
         }
         flush();
         $currentPosition += 1024 * 16;
      }

      exit(0);
   }

   /**
    * Deletes the packages related to the entity
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      $package = new static();
      $package->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);
   }
}
