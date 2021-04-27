<?php
/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

include_file('3rdparty', 'mosquitto_topic_matches_sub', 'php', 'jMQTT');

class jMQTTBase extends eqLogic {

   const DEFAULT_PYTHON_PORT = 55666;
   const DEFAULT_WEBSOCKET_PORT = 55667;

   public static function dependancy_info() {
      return jMQTT::dependancy_info();
   }

   public static function dependancy_install() {
      return array();
   }


   public static function deamon_info() {
      $return = array();
      $return['log'] = get_called_class();
      $return['state'] = 'nok';
      $return['launchable'] = 'nok';

      $python_daemon = false;
      $websocket_daemon = false;

      $pid_file1 = jeedom::getTmpFolder(get_called_class()) . '/jmqttd.py.pid';
      if (file_exists($pid_file1)) {
         if (@posix_getsid(trim(file_get_contents($pid_file1)))) {
            $python_daemon = true;
         } else {
            shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file1 . ' 2>&1 > /dev/null');
         }
      }

      $pid_file2 = jeedom::getTmpFolder(get_called_class()) . '/jmqttd.php.pid';
      if (file_exists($pid_file2)) {
         if (@posix_getsid(trim(file_get_contents($pid_file2)))) {
            $websocket_daemon = true;
         } else {
            shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file2 . ' 2>&1 > /dev/null');
         }
      }

      if($python_daemon && $websocket_daemon){
         $return['state'] = 'ok';
      }

      if (config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) != config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_WEBSOCKET_PORT)) {
         $return['launchable'] = 'ok';
      }
      return $return;
   }

   public static function deamon_start() {
      self::deamon_stop();
      $daemon_info = self::deamon_info();
      if ($daemon_info['launchable'] != 'ok') {
         throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
      }

      // Check python daemon port is available
      $output=null;
      $retval=null;
      exec(system::getCmdSudo() . 'fuser ' . config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . '/tcp', $output, $retval);
      if ($retval == 0 && count($output) > 0) {
         $pid = trim($output[0]);
         exec(system::getCmdSudo() . 'ps -p ' . $pid . ' -o command=', $output, $retval);
         if ($retval == 0 && count($output) > 0) $commandline = $output[0];
         log::add(get_called_class(), 'error', 'Le port du démon python (' . config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . ') est utilisé par le pid ' . $pid . ' : ' . $commandline);
         throw new Exception(__('Le port du démon python (' . config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . ') est utilisé par un autre process', __FILE__));
      }

      // Check websocket daemon port is available
      $output=null;
      $retval=null;
      exec(system::getCmdSudo() . 'fuser ' . config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . '/tcp', $output, $retval);
      if ($retval == 0 && count($output) > 0) {
         $pid = trim($output[0]);
         exec(system::getCmdSudo() . 'ps -p ' . $pid . ' -o command=', $output, $retval);
         if ($retval == 0 && count($output) > 0) $commandline = $output[0];
         log::add(get_called_class(), 'error', 'Le port du démon websocket (' . config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . ') est utilisé par le pid ' . $pid . ' : ' . $commandline);
         throw new Exception(__('Le port du démon websocket (' . config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT) . ') est utilisé par un autre process', __FILE__));
      }

      $path1 = realpath(dirname(__FILE__) . '/../../resources/jmqttd');
      $cmd1 = 'python3 ' . $path1 . '/jmqttd.py';
      $cmd1 .= ' --plugin ' . get_called_class();
      $cmd1 .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(get_called_class()));
      $cmd1 .= ' --socketport ' . config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT);
      $cmd1 .= ' --apikey ' . jeedom::getApiKey(get_called_class());
      $cmd1 .= ' --pid ' . jeedom::getTmpFolder(get_called_class()) . '/jmqttd.py.pid';
      log::add(get_called_class(), 'info', 'Lancement du démon python jMQTT pour le plugin '.get_called_class());
      $result1 = exec($cmd1 . ' >> ' . log::getPathToLog(get_called_class().'_daemon') . ' 2>&1 &');

      $path2 = realpath(dirname(__FILE__) . '/../../core/php/');
      $cmd2 = 'php ' . $path2 . '/jmqttd.php';
      $cmd2 .= ' --plugin ' . get_called_class();
      $cmd2 .= ' --socketport ' . config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_WEBSOCKET_PORT);
      $cmd2 .= ' --pid ' . jeedom::getTmpFolder(get_called_class()) . '/jmqttd.php.pid';
      log::add(get_called_class(), 'info', 'Lancement du démon websocket jMQTT pour le plugin '.get_called_class());
      $result2 = exec($cmd2 . ' >> ' . log::getPathToLog(get_called_class()) . ' 2>&1 &');

      $i = 0;
      while ($i < 10) {
         $daemon_info = self::deamon_info();
         if ($daemon_info['state'] == 'ok') {
            break;
         }
         sleep(1);
         $i++;
      }
      if ($i >= 10) {
         log::add(get_called_class(), 'error', __('Impossible de lancer le démon jMQTT, vérifiez le log',__FILE__), 'unableStartDaemon');
         return false;
      }
      message::removeAll(get_called_class(), 'unableStartDaemon');
      return true;
   }

   public static function deamon_stop() {
      $pid_file1 = jeedom::getTmpFolder(get_called_class()) . '/jmqttd.py.pid';
      if (file_exists($pid_file1)) {
         $pid1 = intval(trim(file_get_contents($pid_file1)));
         system::kill($pid1, false);
         //wait up to 10 seconds for python daemon stop
         for ($i = 1; $i <= 40; $i++) {
            if (! @posix_getsid($pid1)) break;
            usleep(250000);
         }
      }
      $pid_file2 = jeedom::getTmpFolder(get_called_class()) . '/jmqttd.php.pid';
      if (file_exists($pid_file2)) {
         $pid2 = intval(trim(file_get_contents($pid_file2)));
         system::kill($pid2, false);
         //wait up to 10 seconds for php daemon stop
         for ($i = 1; $i <= 40; $i++) {
            if (! @posix_getsid($pid2)) break;
            usleep(250000);
         }
      }
   }

   public static function on_daemon_connect($id) {
      log::add(get_called_class(), 'debug', 'You need to implement "public static function on_daemon_connect($id)" in the class \''.get_called_class().'\' to handle daemon connect event.');
   }
   public static function on_daemon_disconnect($id) {
      log::add(get_called_class(), 'debug', 'You need to implement "public static function on_daemon_disconnect($id)" in the class \''.get_called_class().'\' to handle daemon disconnect event.');
   }
   public static function on_mqtt_connect($id) {
      log::add(get_called_class(), 'debug', 'You need to implement "public static function on_mqtt_connect($id)" in the class \''.get_called_class().'\' to handle mqtt connect event.');
   }
   public static function on_mqtt_disconnect($id) {
      log::add(get_called_class(), 'debug', 'You need to implement "public static function on_mqtt_disconnect($id)" in the class \''.get_called_class().'\' to handle mqtt disconnect event.');
   }
   public static function on_mqtt_message($id, $topic, $payload, $qos, $retain) {
      log::add(get_called_class(), 'debug', 'You need to implement "public static function on_mqtt_message($id, $topic, $payload, $qos, $retain)" in the class \''.get_called_class().'\' to handle mqtt messages.');
   }
   

   protected static function send_to_mqtt_daemon($params) {
      $daemon_info = self::deamon_info();
      if ($daemon_info['state'] != 'ok') {
         throw new Exception("Le démon n'est pas démarré");
      }
      $params['apikey'] = jeedom::getApiKey(get_called_class());
      $payload = json_encode($params);
      $socket = socket_create(AF_INET, SOCK_STREAM, 0);
      socket_connect($socket, '127.0.0.1', config::byKey('pythonsocketport', get_called_class(), get_called_class()::DEFAULT_PYTHON_PORT));
      socket_write($socket, $payload, strlen($payload));
      socket_close($socket);
   }

   public static function new_mqtt_client($id, $hostname, $port = 1883, $clientid = '', $statustopic = '', $username = '', $password = '') {
      $params['cmd']='newMqttClient';
      $params['id']=$id;
      $params['callback']='ws://127.0.0.1:'.config::byKey('websocketport', get_called_class(), get_called_class()::DEFAULT_WEBSOCKET_PORT).'/plugins/jMQTT/core/php/jmqttd.php';
      $params['hostname']=$hostname;
      $params['port']=$port;
      $params['clientid']=$clientid;
      $params['statustopic']=$statustopic;
      $params['username']=$username;
      $params['password']=$password;
      get_called_class()::send_to_mqtt_daemon($params);
   }

   public static function remove_mqtt_client($id) {
      $params['cmd']='removeMqttClient';
      $params['id']=$id;
      get_called_class()::send_to_mqtt_daemon($params);
   }

   public static function subscribe_mqtt_topic($id, $topic, $qos = 1) {
      $params['cmd']='subscribeTopic';
      $params['id']=$id;
      $params['topic']=$topic;
      $params['qos']=$qos;
      get_called_class()::send_to_mqtt_daemon($params);
   }

   public static function unsubscribe_mqtt_topic($id, $topic) {
      $params['cmd']='unsubscribeTopic';
      $params['id']=$id;
      $params['topic']=$topic;
      get_called_class()::send_to_mqtt_daemon($params);
   }

   public static function send_mqtt_message($id, $topic, $payload, $qos = 1, $retain = false) {
      $params['cmd']='messageOut';
      $params['id']=$id;
      $params['topic']=$topic;
      $params['payload']=$payload;
      $params['qos']=$qos;
      $params['retain']=$retain;
      get_called_class()::send_to_mqtt_daemon($params);
   }
}