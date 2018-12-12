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
 * @author    Domingo Oropeza <doropeza@teclib.com>
 * @copyright Copyright © 2018 Teclib
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Mqtt;

use GlpiPlugin\Flyvemdm\Interfaces\BrokerEnvelopeItemInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

final class MqttEnvelope implements BrokerEnvelopeItemInterface {

   private $context;

   /**
    * MqttEnvelope constructor.
    * @param array $context
    */
   public function __construct(array $context) {
      if (!isset($context['topic'])) {
         throw new \InvalidArgumentException(__('A topic argument is needed', 'flyvemdm'));
      }

      if (!isset($context['qos'])) {
         $context['qos'] = 0;
      }
      if (!isset($context['retain'])) {
         $context['retain'] = 0;
      }

      $this->context = $context;
   }

   /**
    * @param $name
    * @return mixed
    */
   public function getContext($name) {
      return $this->context[$name];
   }
}