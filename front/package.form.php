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

// Most content of this file has been altered to make a temporary endpoint for package upload
// TODO: urgent - handle file uploads from the rest api

include ('../../../inc/includes.php');
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
   header("OK", false, 200);
   die();
}

// Get session from token (really ugly !) $_SESSION
$api = new APIRest();
$api->parseIncomingParams();
$api->retrieveSession();

//Session::checkRight("flyvemdm:flyvemdm", PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE); $_SESSION
if (! Session::haveRight('flyvemdm:flyvemdm', PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE)) {
   header("Not allowed", false, 401);
   die();
}

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$package = new PluginFlyvemdmPackage();
$_POST['add'] = '';
if (isset($_POST['add'])) {
   //$package->check(-1, CREATE, $_POST);
   $jsonAnswer = array();
   if ($package->canCreate()) {
      if ($newID = $package->add($_POST)) {
         $jsonAnswer = [
               'id'  => $newID,
         ];
      }
   } else {
      header("Not allowed", false, 401);
   }
   echo json_encode($jsonAnswer, JSON_UNESCAPED_SLASHES);
   die();
   //Html::back();
} else if (isset($_POST['update'])) {
   $package->check($_POST['id'], UPDATE, $_POST);
   $package->update($_POST);
   Html::back();
} else if (isset($_POST["purge"])) {
   $package->check($_POST['id'], PURGE);
   $package->delete($_POST, 1);
   $package->redirectToList();
} else {
   die();
   Html::header(
         PluginFlyvemdmPackage::getTypeName(Session::getPluralNumber()),
         "",
         "tools",
         "PluginFlyvemdmMenu",
         "package"
   );
   $package->display(array('id' => $_GET["id"],
         'withtemplate' => $_GET["withtemplate"]));

   // Footer
   if (strstr($_SERVER['PHP_SELF'], "popup")) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
