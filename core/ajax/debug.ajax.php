<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
    require_once __DIR__ . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception('401 - Unauthorized access');
    }

    require_once __DIR__ . '/../../core/class/jMQTT.class.php';
    ajax::init();
    $action = init('action');

// -------------------- Config Daemon --------------------
    if ($action == 'configGetInternal') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        $res[] = array('header' => '', 'data' => config::searchKey('', "jMQTT"));
        ajax::success($res);
    }
    if ($action == 'configSetInternal') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': key='.init('key').' value='.init('val'));
        config::save(init('key'), json_decode(init('val')), 'jMQTT');
        ajax::success();
    }
    if ($action == 'configDelInternal') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': key='.init('key'));
        config::remove(init('key'), 'jMQTT');
        ajax::success();
    }

// -------------------- Config eqBroker / eqLogic --------------------
    if ($action == 'configGetBrokers') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (jMQTT::getBrokers() as $eqBroker) {
            $data = array();
            foreach (utils::o2a($eqBroker)['configuration'] as $k => $val)
                $data[] = array('key' => $k, 'value' => $val);
            $header = $eqBroker->getHumanName().' (id: '.$eqBroker->getId().')';
            $res[] = array('header' => $header, 'id' => $eqBroker->getId(), 'data' => $data);
        }
        ajax::success($res);
    }
    if ($action == 'configGetEquipments') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (jMQTT::getNonBrokers() as $brk)
            foreach ($brk as $eqLogic) {
                $data = array();
                foreach (utils::o2a($eqLogic)['configuration'] as $k => $val)
                    $data[] = array('key' => $k, 'value' => $val);
                $header = $eqLogic->getHumanName().' (id: '.$eqLogic->getId().')';
                $res[] = array('header' => $header, 'id' => $eqLogic->getId(), 'data' => $data);
            }
        ajax::success($res);
    }
    if ($action == 'configSetBrkAndEqpt') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': id='.init('id').' key='.init('key').' value='.init('val'));
        jMQTT::byId(init('id'))->setConfiguration(init('key'), json_decode(init('val')))->save();
        ajax::success();
    }
    if ($action == 'configDelBrkAndEqpt') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': id='.init('id').'key='.init('key'));
        jMQTT::byId(init('id'))->setConfiguration(init('key'), null)->save();
        ajax::success();
    }

// -------------------- Config cmd --------------------
    if ($action == 'configGetCommandsInfo') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (cmd::searchConfiguration('', jMQTT::class) as $cmd) {
            if ($cmd->getType() != 'info')
                continue;
            $data = array();
            foreach (utils::o2a($cmd)['configuration'] as $k => $val)
                $data[] = array('key' => $k, 'value' => $val);
            $header = $cmd->getHumanName().' (id: '.$cmd->getId().')';
            $res[] = array('header' => $header, 'id' => $cmd->getId(), 'data' => $data);
        }
        ajax::success($res);
    }
    if ($action == 'configGetCommandsAction') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (cmd::searchConfiguration('', jMQTT::class) as $cmd) {
            if ($cmd->getType() != 'action')
                continue;
            $data = array();
            foreach (utils::o2a($cmd)['configuration'] as $k => $val)
                $data[] = array('key' => $k, 'value' => $val);
            $header = $cmd->getHumanName().' (id: '.$cmd->getId().')';
            $res[] = array('header' => $header, 'id' => $cmd->getId(), 'data' => $data);
        }
        ajax::success($res);
    }
    if ($action == 'configSetCommands') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': id='.init('id').' key='.init('key').' value='.init('val'));
        jMQTTCmd::byId(init('id'))->setConfiguration(init('key'), json_decode(init('val')))->save();
        ajax::success();
    }
    if ($action == 'configDelCommands') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': id='.init('id').'key='.init('key'));
        jMQTTCmd::byId(init('id'))->setConfiguration(init('key'), null)->save();
        ajax::success();
    }

// -------------------- Cache Daemon --------------------
    if ($action == 'cacheGetInternal') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $cacheKeys = array();
        $cacheKeys[] = 'dependancyjMQTT';
        $cacheKeys[] = 'jMQTT::' . jMQTTConst::CACHE_DAEMON_LAST_RCV;
        $cacheKeys[] = 'jMQTT::' . jMQTTConst::CACHE_DAEMON_LAST_SND;
        $cacheKeys[] = 'jMQTT::' . jMQTTConst::CACHE_DAEMON_PORT;
        $cacheKeys[] = 'jMQTT::' . jMQTTConst::CACHE_DAEMON_UID;
        $cacheKeys[] = 'jMQTT::' . jMQTTConst::CACHE_JMQTT_NEXT_STATS;
        // $cacheKeys[] = 'jMQTT::dummy';
        // $cacheKeys[] = ;
        $data = array();
        foreach ($cacheKeys as $k)
            $data[] = array('key' => $k, 'value' => cache::byKey($k)->getValue(null));
        $res = array();
        $res[] = array('header' => '', 'data' => $data);
        ajax::success($res);
    }
// -------------------- Cache eqBroker --------------------
    if ($action == 'cacheGetBrokers') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (jMQTT::getBrokers() as $brk) {
            $cacheBrkKeys = array();
            $cacheBrkKeys[] = 'eqLogicCacheAttr'.$brk->getId();
            $cacheBrkKeys[] = 'eqLogicStatusAttr'.$brk->getId();
            $data = array();
            foreach ($cacheBrkKeys as $k) {
                $val = cache::byKey($k)->getValue(null);
                if (!is_null($val))
                    $data[] = array('key' => $k, 'value' => $val);
            }
            if ($data !== array()) {
                $header = $brk->getHumanName().' (id: '.$brk->getId().')';
                $res[] = array('header' => $header, 'data' => $data);
            }
        }
        ajax::success($res);
    }
// -------------------- Cache eqLogic --------------------
    if ($action == 'cacheGetEquipments') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach(jMQTT::getNonBrokers() as $brk) {
            foreach ($brk as $eqpt) {
                $cacheEqptKeys = array();
                $cacheEqptKeys[] = 'jMQTT::' . $eqpt->getId() . '::' . jMQTTConst::CACHE_IGNORE_TOPIC_MISMATCH;
                // $cacheEqptKeys[] = 'jMQTT::' . $eqpt->getId() . '::' . jMQTTConst::CACHE_MQTTCLIENT_CONNECTED;
                $cacheEqptKeys[] = 'eqLogicCacheAttr'.$eqpt->getId();
                $cacheEqptKeys[] = 'eqLogicStatusAttr'.$eqpt->getId();
                $data = array();
                foreach ($cacheEqptKeys as $k) {
                    $val = cache::byKey($k)->getValue(null);
                    if (!is_null($val))
                        $data[] = array('key' => $k, 'value' => $val);
                }
                if ($data !== array()) {
                    $header = $eqpt->getHumanName().' (id: '.$eqpt->getId().')';
                    $res[] = array('header' => $header, 'data' => $data);
                }
            }
        }
        ajax::success($res);
    }
// -------------------- Cache cmd --------------------
    if ($action == 'cacheGetCommandsInfo') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (cmd::searchConfiguration('', jMQTT::class) as $cmd) {
            if ($cmd->getType() != 'info')
                continue;
            $cacheCmdKeys = array();
            $cacheCmdKeys[] = 'cmdCacheAttr'.$cmd->getId();
            $cacheCmdKeys[] = 'cmd'.$cmd->getId();
            $data = array();
            foreach ($cacheCmdKeys as $k) {
                $val = cache::byKey($k)->getValue(null);
                if (!is_null($val))
                    $data[] = array('key' => $k, 'value' => $val);
            }
            if ($data !== array()) {
                $header = $cmd->getHumanName().' (id:'.$cmd->getId().')';
                $res[] = array('header' => $header, 'data' => $data);
            }
        }
        ajax::success($res);
    }
    if ($action == 'cacheGetCommandsAction') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        $res = array();
        foreach (cmd::searchConfiguration('', jMQTT::class) as $cmd) {
            if ($cmd->getType() != 'action')
                continue;
            $cacheCmdKeys = array();
            $cacheCmdKeys[] = 'cmdCacheAttr'.$cmd->getId();
            $cacheCmdKeys[] = 'cmd'.$cmd->getId();
            $data = array();
            foreach ($cacheCmdKeys as $k) {
                $val = cache::byKey($k)->getValue(null);
                if (!is_null($val))
                    $data[] = array('key' => $k, 'value' => $val);
            }
            if ($data !== array()) {
                $header = $cmd->getHumanName().' (id:'.$cmd->getId().')';
                $res[] = array('header' => $header, 'data' => $data);
            }
        }
        ajax::success($res);
    }

// -------------------- Cache set / delete --------------------
    if ($action == 'cacheSet') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': key='.init('key').' value='.init('val'));
        cache::set(init('key'), json_decode(init('val'), true));
        ajax::success();
    }
    if ($action == 'cacheDel') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': key='.init('key'));
        cache::delete(init('key'));
        ajax::success();
    }

// -------------------- Send raw data to Daemon --------------------
    if ($action == 'sendToDaemon') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': data='.init('data'));
        $data = json_decode(init('data'), true);
        if (is_null($data) || !is_array($data)) {
            ajax::error(__('Format invalide', __FILE__));
        }
        // Send to Daemon
        jMQTTComToDaemon::send($data);
        ajax::success();
    }
    if ($action == 'sendToJeedom') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action.': data='.init('data'));
        $data = json_decode(init('data'), true);
        if (is_null($data) || !is_array($data)) {
            ajax::error(__('Format invalide', __FILE__));
        }
        // Prepare url
        $callbackURL = jMQTTDaemon::get_callback_url();
        // To fix issue: https://community.jeedom.com/t/87727/39
        if ((file_exists('/.dockerenv') || config::byKey('forceDocker', 'jMQTT', '0')) && config::byKey('urlOverrideEnable', 'jMQTT', '0') == '1')
            $callbackURL = config::byKey('urlOverrideValue', 'jMQTT', $callbackURL);
        $url = $callbackURL . '?apikey=' . jeedom::getApiKey('jMQTT') . '&uid=' . (@cache::byKey('jMQTT::'.jMQTTConst::CACHE_DAEMON_UID)->getValue("0:0"));

        // Send to Jeedom
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, init('data'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        ajax::success($response);
    }

// -------------------- Simulate dangerous actions --------------------
    if ($action == 'depCheck') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        plugin::byId('jMQTT')->dependancy_info(true);
        ajax::success();
    }
    if ($action == 'depDelete') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        jMQTTDaemon::stop();
        exec('sudo rm -rf '.__DIR__.'/../../resources/JsonPath-PHP/composer.lock');
        exec('sudo rm -rf '.__DIR__.'/../../resources/JsonPath-PHP/vendor');
        ajax::success();
    }
    if ($action == 'venvDelete') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        jMQTTDaemon::stop();
        exec('sudo rm -rf '.__DIR__.'/../../resources/jmqttd/venv');
        ajax::success();
    }
    if ($action == 'dynContentDelete') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        jMQTTDaemon::stop();
        exec('sudo rm -rf '.__DIR__.'/../../resources/jmqttd/__pycache__');
        exec('sudo rm -rf '.jeedom::getTmpFolder('jMQTT').'/rt*.json');
        ajax::success();
    }
    if ($action == 'threadDump') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        // Get cached PID and PORT
        $cuid = @cache::byKey('jMQTT::'.jMQTTConst::CACHE_DAEMON_UID)->getValue("0:0");
        list($cpid, $cport) = array_map('intval', explode(":", $cuid));
        // If PID is unavailable or not running
        if ($cpid == 0 || !@posix_getsid($cpid))
            throw new Exception(__("Le PID du démon n'a pas été trouvé, est-il lancé ?", __FILE__));

        // Else send signal SIGUSR1
        posix_kill($cpid, 10);
        ajax::success();
    }
    if ($action == 'statsSend') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        cache::set('jMQTT::nextStats', time() - 300);
        jMQTTDaemon::pluginStats();
        ajax::success();
    }

    // TODO: Debug modal: implement missing actions
    //  pidFileDelete, hbStop, reInstall, logVerbose
    //  listenersCreate, listenersRemove
    //  labels: enhancement, php
    if ($action == 'pidFileDelete') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }
    if ($action == 'hbStop') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }
    if ($action == 'reInstall') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }
    if ($action == 'listenersRemove') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }
    if ($action == 'listenersCreate') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }
    if ($action == 'logVerbose') {
        jMQTT::logger('debug', 'debug.ajax.php: ' . $action);
        throw new Exception('TODO, not implemented');
    }

    throw new Exception(__('Aucune méthode Ajax ne correspond à :', __FILE__) . ' ' . secureXSS($action));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
