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

class PluginFlyvemdmPolicyDeployfile extends CommonTestCase {

   private $dataField = [
      'group'     => 'file',
      'symbol'    => 'deployFile',
      'type_data' => '',
      'unicity'   => '0',
   ];

   protected function providerCreatePolicy() {
      $item = $this->createDummyFile(0);
      $itemId = $item->getID();
      return [
         'Check values exist'                         => [
            'data'     => [null, null, null],
            'expected' => [false, 'A destination and the remove on delete flag are mandatory'],
         ],
         'Check remove_on_delete is boolean'          => [
            'data'     => [['destination' => 'target', 'remove_on_delete' => ''], null, null],
            'expected' => [false, 'The remove on delete flag must be 0 or 1'],
         ],
         'Check the itemtype is a file'               => [
            'data'     => [['destination' => 'target', 'remove_on_delete' => 0], null, null],
            'expected' => [false, 'You must choose a file to apply this policy'],
         ],
         'Check the file exists'                      => [
            'data'     => [
               ['destination' => 'target', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               '-1',
            ],
            'expected' => [false, 'The file does not exists'],
         ],
         'Check relative directory expression 1'      => [
            'data'     => [
               ['destination' => 'target/../file.txt', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 2'      => [
            'data'     => [
               ['destination' => 'target/./file.txt', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 3'      => [
            'data'     => [
               ['destination' => 'target/../', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 4'      => [
            'data'     => [
               ['destination' => 'target/./', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 5'      => [
            'data'     => [
               ['destination' => '/../file.txt', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 6'      => [
            'data'     => [
               ['destination' => '/./file.txt', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check double directory separator'           => [
            'data'     => [
               ['destination' => 'target//file.txt', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 1' => [
            'data'     => [
               ['destination' => '/file.ext', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 2' => [
            'data'     => [
               ['destination' => 'file.ext', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 3' => [
            'data'     => [
               ['destination' => '', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 4' => [
            'data'     => [
               ['destination' => '/folder/file.ext', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Valid check 1'                              => [
            'data'     => [
               ['destination' => '%SDCARD%/', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [true],
         ],
         'Valid check 2'                              => [
            'data'     => [
               ['destination' => '%SDCARD%', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [true],
         ],
         'Valid check 3'                              => [
            'data'     => [
               ['destination' => '%SDCARD%/file.ext', 'remove_on_delete' => 0],
               \PluginFlyvemdmFile::class,
               $itemId,
            ],
            'expected' => [true],
         ],
      ];
   }

   /**
    * @dataProvider providerCreatePolicy
    * @tags testCreatePolicy
    * @param array $data
    * @param array $expected
    */
   public function testCreatePolicy($data, $expected) {
      list($policy) = $this->createNewPolicyInstance();
      $success = $policy->integrityCheck($data[0], $data[1], $data[2]);
      $this->boolean($success)->isEqualTo($expected[0]);
      if (!$expected[0]) {
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($expected[1]);
         unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      }
   }

   private function createNewPolicyInstance() {
      $policyData = new \PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy) = $this->createNewPolicyInstance();

      $this->boolean($policy->getBrokerMessage(null, null, null))->isFalse();
      $item = $this->createDummyFile(0);
      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":0}';
      $result = $policy->getBrokerMessage($value, $item->getType(), $item->getID());
      $this->array($result)->hasKeys(['id', 'version', $this->dataField['symbol']])
         ->string($result['id'])->isEqualTo($item->getID())
         ->string($result['version'])->isEqualTo("1")
         ->string($result[$this->dataField['symbol']])->isEqualTo('%SDCARD%/filename.ext/');
   }

   /**
    * @tags testUnicityCheck
    */
   public function testUnicityCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance(\PluginFlyvemdmFleet::class);
      $mockInstance->getMockController()->getID = 1;
      $fileInDb = $this->createDummyFile(0);
      $this->boolean($policy->unicityCheck(['destination' => 'filename.ext'],
         \PluginFlyvemdmFile::class, $fileInDb->getID(), $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testConflictCheck
    */
   public function testConflictCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance(\PluginFlyvemdmFleet::class);
      $mockInstance->getMockController()->getID = 1;
      $fileInDb = $this->createDummyFile(0);
      $this->boolean($policy->conflictCheck(['destination' => 'filename.ext'],
         \PluginFlyvemdmFile::class, $fileInDb->getID(), $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testPre_unapply
    */
   public function testPre_unapply() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance(\PluginFlyvemdmFleet::class);
      $mockInstance->getMockController()->getID = 1;
      $fileInDb = $this->createDummyFile(0);

      $this->boolean($policy->pre_unapply(null, null, null, $mockInstance))->isFalse();

      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":0}';
      $this->boolean($policy->pre_unapply($value, \PluginFlyvemdmFile::class,
         $fileInDb->getID(), $mockInstance))->isTrue();

      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":1}';
      $this->boolean($policy->pre_unapply($value, \PluginFlyvemdmFile::class,
         -1, $mockInstance))->isFalse();
      // TODO: finish this test
   }

   /**
    * @tags testShowValueInput
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();
      $value = $policy->showValueInput();
      $this->string($value)
         ->contains('dropdown_items_id')->contains('ajax/getDropdownValue.php')
         ->contains('input type="hidden" name="itemtype" value="PluginFlyvemdmFile"')
         ->contains('input type="text" name="value[destination]" value=""');

      $matches = null;
      preg_match(
         '/.*<select[^>]*name=\'value\[remove_on_delete\]\'[^>]*>.*/',
         $value,
         $matches
      );
      $this->array($matches)->hasSize(1);
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockInstance->getMockController()->getField = 0;
      $mockInstance->getMockController()->getField[2] = 1;
      $mockInstance->getMockController()->getField[3] = '{"destination":"path"}';
      $this->string($policy->showValue($mockInstance))->isEqualTo(NOT_AVAILABLE);
      // TODO: make this test work directly by @tags with a clean DB.
      // $this->string($policy->showValue($mockInstance))->isEqualTo('path/filename.ext');
   }

   /**
    * @tags testPreprocessFormData
    */
   public function testPreprocessFormData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->array($policy->preprocessFormData($input = ['invalidKey' => 'invalidValue']))
         ->isEqualTo($input);
      $this->array($output = $policy->preprocessFormData([
         'destination_base' => 1,
         'value'            => ['destination' => 'targetString'],
      ]))->string($output['value']['destination'])->isEqualTo('%SDCARD%targetString');
   }

   public function providerFilterStatus() {
      $policyBaseTest = new PluginFlyvemdmPolicyBase();
      $statuses = $policyBaseTest->providerFilterStatus();
      $statuses = array_merge($statuses, [
         [
            'status' => 'waiting',
            'expected' => 'waiting'
         ],
      ]);
      $this->array($statuses)->size->isEqualTo(8);

      $statuses = array_merge($statuses, [
         [
            'status' => 'invalid',
            'expected' => null
         ],
      ]);

      return $statuses;
   }

   /**
    * @dataProvider providerFilterStatus
    * @tags testFilterStatus
    * @param string $status
    * @param string $expected
    */
   public function testFilterStatus($status, $expected) {
      $policyDefinition = new \PluginFlyvemdmPolicy();
      $policyDefinition->getFromDBBySymbol('deployFile');
      $policyObject = new \PluginFlyvemdmPolicyDeployfile($policyDefinition);
      $this->variable($policyObject->filterStatus($status))->isEqualTo($expected);
   }
}
