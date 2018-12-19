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

namespace GlpiPlugin\Flyvemdm\Mqtt;

use GlpiPlugin\Flyvemdm\Interfaces\BrokerReceiverInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class MqttReceiver implements BrokerReceiverInterface {

   private $connection;

   public function __construct(MqttConnection $connection) {
      $this->connection = $connection;
   }

   /**
    * Receive some messages to the given handler.
    *
    * The handler will have, as argument, the received PluginFlyvemdmBrokerEnvelope containing the message.
    * Note that this envelope can be `null` if the timeout to receive something has expired.
    *
    * @param callable $handler
    * @return void
    */
   public function receive(callable $handler) {
      $connection = $this->connection;
      $connection::getInstance();
      $mqtt = $connection->getMQTT();
      if ($mqtt === false) {
         exit(1);
      }

      $topics = ['#' => 0];
      $mqtt->setHandler(new MqttReceiveMessageHandler(new \PluginFlyvemdmMqttlog()));
      $mqtt->subscribe($topics);

      while (!$connection->mustDisconnect()) {
         try {
            $mqtt->loop();
         } catch (\Exception $e) {
            $error = "Exception while listening MQTT messages : \n" . $e->getMessage();
            $trace = $e->getTraceAsString();

            \Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
            $mqtt->reconnect(true);
            $mqtt->subscribe($topics);
         }
      }
   }

   /**
    * Stop receiving some messages.
    * @return void
    */
   public function stop() {
      $this->connection->disconnect();
   }
}