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

class SelfRegistrationUserTestCase extends CommonTestCase {


   public static function setupBeforeClass() {
      parent::setupBeforeClass();
      self::resetState();
      self::setupGLPIFramework();
   }

   public function setUp() {
      self::setupGLPIFramework();
      $user = new User();
      $user->getFromDBbyName('flyvenologin');
      $_REQUEST['user_token'] = $user->getField('personal_token');
      $this->assertTrue(self::login('', '', false));
   }

   public function tearDown() {
      unset($_REQUEST['user_token']);
   }

}
