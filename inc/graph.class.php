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
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmGraph extends CommonDBTM
{

   /**
    * Displays the graphic of the invitations
    * @return string HTML snippet for Pie graph
    */
   public function showInvitationsGraph() {
      $dbUtils = new DbUtils();

      $pendingCount = $dbUtils->countElementsInTableForMyEntities(
         PluginFlyvemdmInvitation::getTable(),
         ['status' => 'pending']
      );

      $doneCount = $dbUtils->countElementsInTableForMyEntities(
         PluginFlyvemdmInvitation::getTable(),
         ['status' => 'done']
      );

      if (($pendingCount + $doneCount) == 0) {
         return '<h2 class="center">' . __('Invitations', 'flyvemdm')
            . '</h2><br><div class="center">'
            . __('No invitations stats available, send one first.', 'flyvemdm')
            . '</div>';
      }

      $out = $this->displayStackedBarGraph(
            __('Invitations', 'flyvemdm'),
            [
               __('Done', 'flyvemdm'),
               __('Pending', 'flyvemdm')
            ],
            [
               $doneCount,
               $pendingCount,
            ],
            false
      );

      return $out;
   }

   /**
    * Displays the devices per operating system version
    * @return string a HTML with the devices according their operating system version
    */
   public function showDevicesPerOSVersion() {
      global $DB;

      $out = '';

      $config = Config::getConfigurationValues('flyvemdm', ['computertypes_id']);
      $computerTypeId = $config['computertypes_id'];
      $computerTable = Computer::getTable();
      $itemOperatingSystemTable = Item_OperatingSystem::getTable();
      $operatingSystemTable = OperatingSystem::getTable();
      $operatingSystemVersionTable = OperatingSystemVersion::getTable();
      $DbUtil = new DbUtils();
      $entityRestrict = $DbUtil->getEntitiesRestrictRequest(" AND ", $computerTable);
      $query = "SELECT
                  `os`.`name` AS `operatingsystem`,
                  COUNT(*) AS `cpt`
                FROM `$computerTable`
                LEFT JOIN `$itemOperatingSystemTable` AS `i_os`
                  ON (`i_os`.itemtype = 'Computer' AND `i_os`.`items_id` = `$computerTable`.`id`)
                LEFT JOIN `$operatingSystemTable` AS `os`
                  ON (`os`.`id` = `i_os`.`operatingsystems_id`)
                WHERE `$computerTable`.`computertypes_id` = '$computerTypeId' $entityRestrict
                GROUP BY `operatingsystem`";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 0) {
         return '<h2 class="center">' . __('Devices per operating system version', 'flyvemdm')
            . '</h2><br><div class="center">'
            . __('No devices stats available, enrol or import your mobile computer assets first',
               'flyvemdm')
            . '</div>';
      }

      if (!$result || $DB->numrows($result) <= 0) {
         return $out;
      }
      $osNames = [];
      $quantityPerOs = [];
      while ($row = $DB->fetch_assoc($result)) {
         $osNames[] = $row['operatingsystem'];
         if (isset($quantityPerOs[$row['operatingsystem']])) {
            $quantityPerOs[$row['operatingsystem']] += $row['cpt'];
         } else {
            $quantityPerOs[$row['operatingsystem']] = $row['cpt'];
         }
      }
      array_walk($quantityPerOs, function (&$value, $key) {
         $value = ['name' => $key, 'data' => $value];
      });

      $stat = new Stat();
      $out = $stat->displayPieGraph(
         __('Devices per operating system version', 'flyvemdm'),
         $osNames,
         $quantityPerOs,
         [],
         false
      );

      return $out;
   }

   /**
    * Display stacked bar graph
    *
    * @param string   $title  Graph title
    * @param string[] $labels Labels to display
    * @param array    $series Series data. An array of the form:
    *                 [
    *                    ['name' => 'a name', 'data' => []],
    *                    ['name' => 'another name', 'data' => []]
    *                 ]
    * @param string[] $options  array of options
    * @param boolean  $display  Whether to display directly; defauts to true
    *
    * @return string|void
    */
   public function displayStackedBarGraph($title, $labels, $series, $options = null, $display = true) {
      $param = [
         'width'   => 900,
         'height'  => 300,
         'tooltip' => true,
         'legend'  => false,
         'animate' => true
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $slug = str_replace('-', '_', Toolbox::slugify($title));
      $this->checkEmptyLabels($labels);
      $out = "<h2 class='center'>$title</h2>";
      $out .= "<div id='$slug' class='chart'></div>";
      $out .= "<script type='text/javascript'>
                  $(function() {
                     var chart_$slug = new Chartist.Bar('#$slug', {
                        labels: ['" . implode('\', \'', Toolbox::addslashes_deep($labels))  . "'],
                        series: [". implode(', ', $series)  ."]
                     }, {
                        low: 0,
                        showArea: true,
                        width: '{$param['width']}',
                        height: '{$param['height']}',
                        fullWidth: true,
                        distributeSeries: true";

      if ($param['legend'] === true || $param['tooltip'] === true) {
         $out .= ", plugins: [";
         if ($param['legend'] === true) {
            $out .= "Chartist.plugins.legend()";
         }
         if ($param['tooltip'] === true) {
            $out .= ($param['legend'] === true ? ',' : '') . "Chartist.plugins.tooltip()";
         }
         $out .= "]";
      }

      $out .= "});";

      if ($param['animate'] === true) {
         $out .= "
                     chart_$slug.on('draw', function(data) {
                        if(data.type === 'bar') {
                           data.element.animate({
                              y2: {
                                 begin: 300 * data.index,
                                 dur: 500,
                                 from: data.y1,
                                 to: data.y2,
                                 easing: Chartist.Svg.Easing.easeOutQuint
                              }
                           });
                        }
                     });
                  });";
      }
      $out .= "</script>";

      if ($display) {
         echo $out;
         return;
      }
      return $out;
   }

   /**
    * Check and replace empty labels (picked from GLPI 9.2)
    *
    * @param array $labels Labels
    *
    * @return void
    */
   private function checkEmptyLabels(&$labels) {
      foreach ($labels as &$label) {
         if (empty($label)) {
            $label = '-';
         }
      }
   }
}
